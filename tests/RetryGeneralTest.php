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
use STS\Backoff\Backoff;

use function JBZoo\Retry\retry;

/**
 * Class HelpersTest
 * @package JBZoo\PHPUnit
 */
class RetryGeneralTest extends PHPUnit
{
    public function testSuccessWithDefaults()
    {
        $result = retry(function () {
            return "success";
        });

        isSame("success", $result);
    }

    public function testFailureWithDefaults()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("failure");

        retry(function () {
            throw new \RuntimeException("failure");
        }, 2);
    }

    public function testStrategy()
    {
        $realNumberOfAttempts = 0;
        $start = microtime(true);

        // We're going to run a test for 100 attempts, just to verify we were able to
        // set our own strategy with a low sleep time.

        try {
            retry(function () use (&$realNumberOfAttempts) {
                $realNumberOfAttempts++;
                throw new \RuntimeException("failure");
            }, 100, new ConstantStrategy(1));
        } catch (\Exception $exception) {
        }

        $end = microtime(true);

        isSame(100, $realNumberOfAttempts);

        $elapsedMS = ($end - $start) * 1000;

        // We expect that this took just a bit over the 100ms that we slept
        isTrue($elapsedMS > 100 && $elapsedMS < 200);
    }

    public function testWaitCap()
    {
        $start = microtime(true);

        // We're going to specify a really long sleep time, but with a short cap to override.

        try {
            retry(function () {
                throw new \RuntimeException("failure");
            }, 2, new ConstantStrategy(100000), 100);
        } catch (\Exception $e) {
        }

        $end = microtime(true);

        $elapsedMS = ($end - $start) * 1000;

        // We expect that this took just a bit over the 100ms that we slept
        isTrue(
            $elapsedMS > 90 && $elapsedMS < 150,
            sprintf("Expected elapsedMS between 90 & 200, got: $elapsedMS\n")
        );
    }

    public function testClassAlias()
    {
        $backoff = new Backoff();

        $backoff->enableJitter();
        isTrue($backoff->jitterEnabled());
        $backoff->disableJitter();
        isFalse($backoff->jitterEnabled());

        $result = $backoff->run(function () {
            return 123;
        });

        isSame(123, $result);
    }

    public function testFunctionAlias()
    {
        $result = \backoff(function () {
            return "success";
        });

        isSame("success", $result);
    }

    public function testUndefinedStrategy()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invalid strategy: UndefinedStrategy");

        $backoff = new Backoff();
        $backoff->setStrategy('UndefinedStrategy');
    }

    public function testErrorHandler()
    {
        $messages = [];

        $retry = new Retry();
        $retry->setErrorHandler(function ($exception, $attempt, $maxAttempts) use (&$messages) {
            $messages[] = "On run {$attempt}/{$maxAttempts} we hit a problem: {$exception->getMessage()}";
        });

        try {
            $retry->run(function () {
                throw new \Error("failure");
            });
        } catch (\Exception $exception) {
        }

        isSame([
            'On run 1/5 we hit a problem: failure',
            'On run 2/5 we hit a problem: failure',
            'On run 3/5 we hit a problem: failure',
            'On run 4/5 we hit a problem: failure'
        ], $messages);
    }
}
