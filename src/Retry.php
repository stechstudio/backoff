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

namespace JBZoo\Retry;

use Closure;
use Exception;
use JBZoo\Retry\Strategies\ConstantStrategy;
use JBZoo\Retry\Strategies\ExponentialStrategy;
use JBZoo\Retry\Strategies\LinearStrategy;
use JBZoo\Retry\Strategies\PolynomialStrategy;

/**
 * Class Retry
 * @package JBZoo\Retry
 */
class Retry
{
    public const DEFAULT_MAX_ATTEMPTS = 5;
    public const DEFAULT_STRATEGY     = 'polynomial';
    public const DEFAULT_JITTER_STATE = false;
    public const DEFAULT_WAIT_CAP     = 2;

    /**
     * This callable should take an 'attempt' integer, and return a wait time in milliseconds
     *
     * @var callable
     */
    protected $strategy;

    /**
     * @var array
     */
    protected $strategies = [
        'constant'    => ConstantStrategy::class,
        'linear'      => LinearStrategy::class,
        'polynomial'  => PolynomialStrategy::class,
        'exponential' => ExponentialStrategy::class
    ];

    /**
     * @var int
     */
    protected $maxAttempts;

    /**
     * The max wait time you want to allow, regardless of what the strategy says
     *
     * @var int In milliseconds
     */
    protected $waitCap = self::DEFAULT_WAIT_CAP;

    /**
     * @var bool
     */
    protected $useJitter = false;

    /**
     * @var array|non-empty-array<int,\Exception>|non-empty-array<int,\Throwable>
     */
    protected $exceptions = [];

    /**
     * This will decide whether to retry or not.
     * @var callable
     */
    protected $decider;

    /**
     * This receive any exceptions we encounter.
     * @var callable
     */
    protected $errorHandler;

    /**
     * @param int      $maxAttempts
     * @param mixed    $strategy
     * @param int      $waitCap
     * @param bool     $useJitter
     * @param callable $decider
     */
    public function __construct(
        $maxAttempts = self::DEFAULT_MAX_ATTEMPTS,
        $strategy = self::DEFAULT_STRATEGY,
        $waitCap = self::DEFAULT_WAIT_CAP,
        $useJitter = self::DEFAULT_JITTER_STATE,
        $decider = null
    ) {
        $this->setMaxAttempts($maxAttempts ?? self::DEFAULT_MAX_ATTEMPTS);
        $this->setStrategy($strategy ?? self::DEFAULT_STRATEGY);
        $this->setJitter($useJitter ?? self::DEFAULT_JITTER_STATE);
        $this->setWaitCap($waitCap);
        $this->setDecider($decider ?? self::getDefaultDecider());
    }

    /**
     * @param int $attempts
     * @return $this
     */
    public function setMaxAttempts(int $attempts): self
    {
        $this->maxAttempts = $attempts;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * @param int $waitCap
     * @return $this
     */
    public function setWaitCap(int $waitCap): self
    {
        $this->waitCap = $waitCap;
        return $this;
    }

    /**
     * @return int
     */
    public function getWaitCap(): int
    {
        return $this->waitCap;
    }

    /**
     * @param bool $useJitter
     * @return $this
     */
    public function setJitter(bool $useJitter): self
    {
        $this->useJitter = $useJitter;
        return $this;
    }

    /**
     * @return $this
     */
    public function enableJitter(): self
    {
        $this->setJitter(true);
        return $this;
    }

    /**
     * @return $this
     */
    public function disableJitter(): self
    {
        $this->setJitter(false);
        return $this;
    }

    /**
     * @return bool
     */
    public function jitterEnabled(): bool
    {
        return $this->useJitter;
    }

    /**
     * @return callable
     */
    public function getStrategy(): callable
    {
        return $this->strategy;
    }

    /**
     * @param callable|string $strategy
     * @return $this
     */
    public function setStrategy($strategy): self
    {
        $this->strategy = $this->buildStrategy($strategy);
        return $this;
    }

    /**
     * Builds a callable strategy.
     *
     * @param mixed $strategy   Can be a string that matches a key in $strategies, an instance of AbstractStrategy
     *                          (or any other instance that has an __invoke method), a callback function, or
     *                          an integer (which we interpret to mean you want a ConstantStrategy)
     * @return callable
     */
    protected function buildStrategy($strategy): callable
    {
        if (\is_string($strategy) && \array_key_exists($strategy, $this->strategies)) {
            return new $this->strategies[$strategy]();
        }

        if (\is_callable($strategy)) {
            return $strategy;
        }

        throw new \InvalidArgumentException("Invalid strategy: {$strategy}");
    }

    /**
     * @param Closure $callback
     * @return mixed|null
     * @throws Exception
     */
    public function run(Closure $callback)
    {
        $attempt = 0;
        $try = true;
        $result = null;

        while ($try) {
            $exceptionExternal = null;

            $this->wait($attempt);
            try {
                $result = $callback();
            } catch (\Exception $exception) {
                $this->exceptions[] = $exception;
                $exceptionExternal = $exception;
            } catch (\Throwable $exception) {
                if ($exception instanceof \Error) {
                    $exception = new Exception($exception->getMessage(), $exception->getCode(), $exception);
                }
                $this->exceptions[] = $exception;
                $exceptionExternal = $exception;
            }

            $try = \call_user_func($this->decider, ++$attempt, $this->getMaxAttempts(), $result, $exceptionExternal);

            if ($try && isset($this->errorHandler)) {
                \call_user_func($this->errorHandler, $exceptionExternal, $attempt, $this->getMaxAttempts());
            }
        }

        return $result;
    }

    /**
     * Sets the decider callback
     * @param callable $callback
     * @return $this
     */
    public function setDecider(callable $callback): self
    {
        $this->decider = $callback;
        return $this;
    }

    /**
     * Sets the error handler callback
     * @param callable $callback
     * @return $this
     */
    public function setErrorHandler(callable $callback): self
    {
        $this->errorHandler = $callback;
        return $this;
    }

    /**
     * Gets a default decider that simply check exceptions and maxattempts
     * @return Closure
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected static function getDefaultDecider(): Closure
    {
        return static function (
            int $retry,
            int $maxAttempts,
            // @phan-suppress-next-line PhanUnusedClosureParameter
            $result = null,
            ?Exception $exception = null
        ): bool {
            if ($retry >= $maxAttempts && $exception) {
                throw  $exception;
            }

            return $retry < $maxAttempts && $exception;
        };
    }

    /**
     * @param int $attempt
     * @return $this
     */
    public function wait(int $attempt): self
    {
        if ($attempt === 0) {
            return $this;
        }

        \usleep($this->getWaitTime($attempt) * 1000);
        return $this;
    }

    /**
     * @param int $attempt
     * @return int
     */
    public function getWaitTime(int $attempt): int
    {
        $waitTime = \call_user_func($this->getStrategy(), $attempt);
        return $this->jitter($this->cap($waitTime));
    }

    /**
     * @param int $waitTime
     * @return int
     */
    protected function cap(int $waitTime): int
    {
        return \min($this->getWaitCap(), $waitTime);
    }

    /**
     * @param int $waitTime
     * @return int
     */
    protected function jitter(int $waitTime): int
    {
        return $this->jitterEnabled() ? \random_int(0, $waitTime) : $waitTime;
    }
}
