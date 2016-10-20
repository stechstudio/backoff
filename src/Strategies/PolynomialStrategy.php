<?php
namespace STS\Backoff\Strategies;

/**
 * Class PolynomialStrategy
 * @package STS\Backoff\Strategies
 */
/**
 * Class PolynomialStrategy
 * @package STS\Backoff\Strategies
 */
class PolynomialStrategy extends AbstractStrategy
{
    /**
     * @var int|null
     */
    protected $degree = 2;

    /**
     * PolynomialStrategy constructor.
     *
     * @param null $degree
     * @param null $base
     */
    public function __construct($base = null, $degree = null)
    {
        if(!is_null($degree)) {
            $this->degree = $degree;
        }

        parent::__construct($base);
    }

    /**
     * @param $attempt
     *
     * @return int
     */
    public function getWaitTime($attempt)
    {
        return pow($attempt, $this->degree) * $this->base;
    }

    /**
     * @return int|null
     */
    public function getDegree()
    {
        return $this->degree;
    }
}
