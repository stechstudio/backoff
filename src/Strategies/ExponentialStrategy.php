<?php
namespace STS\Backoff\Strategies;

/**
 * Class ExponentialStrategy
 * @package STS\Backoff\Strategies
 */
class ExponentialStrategy extends AbstractStrategy
{
    /**
     * @param int $attempt
     *
     * @return int
     */
    public function getWaitTime($attempt)
    {
        return (int) ($this->base * (pow(2, $attempt - 1)));
    }
}
