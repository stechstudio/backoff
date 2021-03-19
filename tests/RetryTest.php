<?php

/**
 * JBZoo Toolbox - Retry
 *
 * This file is part of the JBZoo Toolbox project.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Retry
 * @license    MIT
 * @copyright  Copyright (C) JBZoo.com, All rights reserved.
 * @link       https://github.com/JBZoo/Retry
 */

declare(strict_types=1);

namespace JBZoo\PHPUnit;

use JBZoo\Retry\Retry;
use JBZoo\Retry\Strategies\ConstantStrategy;
use JBZoo\Retry\Strategies\ExponentialStrategy;
use JBZoo\Retry\Strategies\LinearStrategy;
use JBZoo\Retry\Strategies\PolynomialStrategy;

/**
 * Class RetryTest
 * @package JBZoo\PHPUnit
 */
class RetryTest extends PHPUnit
{
    public function testDefaults()
    {
        $retry = new Retry();

        isSame(5, $retry->getMaxAttempts());
        self::assertInstanceOf(PolynomialStrategy::class, $retry->getStrategy());
        isFalse($retry->jitterEnabled());
    }

    public function testFluidApi()
    {
        $retry = new Retry();

        $retry
            ->setStrategy('constant')
            ->setMaxAttempts(10)
            ->setWaitCap(5)
            ->enableJitter();

        isSame(10, $retry->getMaxAttempts());
        isSame(5, $retry->getWaitCap());
        isTrue($retry->jitterEnabled());
        self::assertInstanceOf(ConstantStrategy::class, $retry->getStrategy());
    }

    public function testConstructorParams()
    {
        $b = new Retry(10, "linear");

        isSame(10, $b->getMaxAttempts());
        self::assertInstanceOf(LinearStrategy::class, $b->getStrategy());
    }

    public function testStrategyKeys()
    {
        $retry = new Retry();

        $retry->setStrategy("constant");
        self::assertInstanceOf(ConstantStrategy::class, $retry->getStrategy());

        $retry->setStrategy("linear");
        self::assertInstanceOf(LinearStrategy::class, $retry->getStrategy());

        $retry->setStrategy("polynomial");
        self::assertInstanceOf(PolynomialStrategy::class, $retry->getStrategy());

        $retry->setStrategy("exponential");
        self::assertInstanceOf(ExponentialStrategy::class, $retry->getStrategy());
    }

    public function testStrategyInstances()
    {
        $retry = new Retry();

        $retry->setStrategy(new ConstantStrategy());
        self::assertInstanceOf(ConstantStrategy::class, $retry->getStrategy());

        $retry->setStrategy(new LinearStrategy());
        self::assertInstanceOf(LinearStrategy::class, $retry->getStrategy());

        $retry->setStrategy(new PolynomialStrategy());
        self::assertInstanceOf(PolynomialStrategy::class, $retry->getStrategy());

        $retry->setStrategy(new ExponentialStrategy());
        self::assertInstanceOf(ExponentialStrategy::class, $retry->getStrategy());
    }

    public function testClosureStrategy()
    {
        $retry = new Retry();

        $strategy = function () {
            return "hi there";
        };

        $retry->setStrategy($strategy);

        isSame("hi there", call_user_func($retry->getStrategy()));
    }

    public function testIntegerReturnsConstantStrategy()
    {
        $retry = new Retry();

        $retry->setStrategy(500);

        self::assertInstanceOf(ConstantStrategy::class, $retry->getStrategy());
    }

    public function testInvalidStrategy()
    {
        $retry = new Retry();

        $this->expectException(\InvalidArgumentException::class);
        $retry->setStrategy("foo");
    }

    public function testWaitTimes()
    {
        $retry = new Retry(1, "linear");

        isSame(100, $retry->getStrategy()->getBase());

        isSame(100, $retry->getWaitTime(1));
        isSame(200, $retry->getWaitTime(2));
    }

    public function testWaitCap()
    {
        $retry = new Retry(1, new LinearStrategy(5000));

        isSame(10000, $retry->getWaitTime(2));

        $retry->setWaitCap(5000);

        isSame(5000, $retry->getWaitTime(2));
    }

    public function testWaitLessOneSecond()
    {
        $retry = new Retry(1, new LinearStrategy(50));

        $start = microtime(true);

        $retry->wait(2);

        $end = microtime(true);

        $elapsedMS = ($end - $start) * 1000;

        // We expect that this took just barely over the 100ms we asked for
        isTrue(
            $elapsedMS > 90 && $elapsedMS < 150,
            "Expected elapsedMS between 90 & 150, got: {$elapsedMS}"
        );
    }

    /**
     * @depends testWaitLessOneSecond
     */
    public function testWaitMoreOneSecond()
    {
        $retry = new Retry(1, new LinearStrategy(400));

        $start = microtime(true);

        $retry->wait(3); // ~1.2 seconds

        $end = microtime(true);

        $elapsedMS = ($end - $start) * 1000;

        // We expect that this took just barely over the 100ms we asked for
        isTrue(
            $elapsedMS > 1200 && $elapsedMS < 1300,
            "Expected elapsedMS between 90 & 150, got: {$elapsedMS}"
        );
    }

    public function testSuccessfulWork()
    {
        $retry = new Retry();

        $result = $retry->run(function () {
            return "done";
        });

        isSame("done", $result);
    }

    public function testFirstAttemptDoesNotCallStrategy()
    {
        $retry = new Retry();
        $retry->setStrategy(function () {
            throw new \Exception("We shouldn't be here");
        });

        $result = $retry->run(function () {
            return "done";
        });

        isSame("done", $result);
    }

    public function testFailedWorkReThrowsException()
    {
        $retry = new Retry(2, new ConstantStrategy(0));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("failure");

        $retry->run(function () {
            throw new \RuntimeException("failure");
        });
    }

    public function testHandleErrorsPhp7()
    {
        $retry = new Retry(2, new ConstantStrategy(0));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Modulo by zero");

        $retry->run(function () {
            if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
                /** @noinspection PhpDivisionByZeroInspection */
                return 1 % 0;
            }

            // Handle version < 7
            throw new \Error("Modulo by zero");
        });
    }

    public function testAttempts()
    {
        $retry = new Retry(10, new ConstantStrategy(0));

        $attempt = 0;

        $result = $retry->run(function () use (&$attempt) {
            $attempt++;

            if ($attempt < 5) {
                throw new \Exception("failure");
            }

            return "success";
        });

        isSame(5, $attempt);
        isSame("success", $result);
    }

    public function testCustomDeciderAttempts()
    {
        $retry = new Retry(10, new ConstantStrategy(0));
        $retry->setDecider(function ($retry, $maxAttempts, $result = null, $exception = null) {
            return !($retry >= $maxAttempts || $result === "success");
        });

        $attempt = 0;

        $result = $retry->run(function () use (&$attempt) {
            $attempt++;

            if ($attempt < 5) {
                throw new \RuntimeException("failure");
            }

            if ($attempt < 7) {
                return 'not yet';
            }

            return "success";
        });

        isSame(7, $attempt);
        isSame("success", $result);
    }

    public function testErrorHandler()
    {
        $log = [];

        $retry = new Retry(10, new ConstantStrategy(0));
        $retry->setErrorHandler(function ($exception, $attempt, $maxAttempts) use (&$log) {
            $log[] = "Attempt $attempt of $maxAttempts: " . $exception->getMessage();
        });

        $attempt = 0;

        $result = $retry->run(function () use (&$attempt) {
            $attempt++;

            if ($attempt < 5) {
                throw new \Exception("failure");
            }

            return "success";
        });

        isSame(4, count($log));
        isSame("Attempt 4 of 10: failure", array_pop($log));
        isSame("success", $result);
    }

    public function testJitter()
    {
        $retry = new Retry(10, new ConstantStrategy(1000));

        // First without jitter
        isSame(1000, $retry->getWaitTime(1));

        // Now with jitter
        $retry->enableJitter();

        // Because it's still possible that I could get 1000 back even with jitter, I'm going to generate two
        $waitTime1 = $retry->getWaitTime(1);
        $waitTime2 = $retry->getWaitTime(1);

        // And I'm banking that I didn't hit the _extremely_ rare chance that both were randomly chosen to be 1000 still
        isTrue($waitTime1 < 1000 || $waitTime2 < 1000);
    }


    public function testUndefinedStrategy()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invalid strategy: UndefinedStrategy");

        $retry = new Retry();
        $retry->setStrategy('UndefinedStrategy');
    }

    public function testErrorHandlerVersion2()
    {
        $messages = [];

        $retry = new Retry();
        $retry->setErrorHandler(function ($exception, $attempt, $maxAttempts) use (&$messages) {
            $messages[] = "On run {$attempt}/{$maxAttempts} we hit a problem: {$exception->getMessage()}";
        });

        try {
            $retry->run(function (int $currentAttempt, int $maxAttempts) {
                throw new \Error("failure #{$currentAttempt}/{$maxAttempts}");
            });
        } catch (\Exception $exception) {
        }

        isSame([
            'On run 1/5 we hit a problem: failure #1/5',
            'On run 2/5 we hit a problem: failure #2/5',
            'On run 3/5 we hit a problem: failure #3/5',
            'On run 4/5 we hit a problem: failure #4/5'
        ], $messages);
    }

    public function testWaitingTime()
    {
        $retry = (new Retry())
            ->setStrategy(new ExponentialStrategy(100));

        isSame([100, 400, 800, 1600, 3200], [
            $retry->getWaitTime(1),
            $retry->getWaitTime(2),
            $retry->getWaitTime(3),
            $retry->getWaitTime(4),
            $retry->getWaitTime(5)
        ]);
    }

    public function testWaitingTimeWithJitter()
    {
        $retry = (new Retry())
            ->setStrategy(new ExponentialStrategy(100))
            ->enableJitter();

        isNotSame([100, 400, 800, 1600, 3200], [
            $retry->getWaitTime(1),
            $retry->getWaitTime(2),
            $retry->getWaitTime(3),
            $retry->getWaitTime(4),
            $retry->getWaitTime(5)
        ]);
    }
}
