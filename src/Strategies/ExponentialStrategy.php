<?php
namespace STS\Backoff\Strategies;

/**
 * Class ExponentialStrategy
 * @package STS\Backoff\Strategies
 */
class ExponentialStrategy extends AbstractStrategy
{
    /**
     * @param $attempt
     *
     * @return int
     */
    public function getWaitTime($attempt)
    {
        return $attempt == 1
            ? $this->base
            : pow(2, $attempt) * $this->base;
    }
}
