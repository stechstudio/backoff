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
use STS\Backoff\Backoff;

use function JBZoo\Retry\retry;

/**
 * Class RetryAliasesTest
 * @package JBZoo\PHPUnit
 */
class RetryAliasesTest extends PHPUnit
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
        isTrue(
            $elapsedMS > 100 && $elapsedMS < 200,
            "Expected elapsedMS between 100 & 200, got: {$elapsedMS}"
        );
    }

    public function testWaitCap()
    {
        $start = microtime(true);

        // We're going to specify a really long sleep time, but with a short cap to override.

        try {
            retry(function () {
                throw new \RuntimeException("failure");
            }, 2, new ConstantStrategy(1000), 100);
        } catch (\Exception $exception) {
        }

        $end = microtime(true);

        $elapsedMS = ($end - $start) * 1000;

        // We expect that this took just a bit over the 100ms that we slept
        isTrue(
            $elapsedMS > 90 && $elapsedMS < 150,
            "Expected elapsedMS between 90 & 150, got: {$elapsedMS}"
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

    public function testJitter()
    {
        $retry = new Retry();
        $retry->setStrategy(new ExponentialStrategy(10));
        $retry->setWaitCap(1000000);

        isFalse($retry->jitterEnabled());
        isSame($retry->getWaitTime(3), $retry->getWaitTime(3));

        $retry->enableJitter();
        isTrue($retry->jitterEnabled());
        isNotSame($retry->getWaitTime(3), $retry->getWaitTime(3));

        $retry->disableJitter();
        isFalse($retry->jitterEnabled());
        isSame($retry->getWaitTime(3), $retry->getWaitTime(3));
    }

    public function testFunctionAlias()
    {
        $result = \backoff(function () {
            return "success";
        });

        isSame("success", $result);
    }
}
