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

namespace JBZoo\Retry\Strategies;

/**
 * Class PolynomialStrategy
 * @package JBZoo\Retry\Strategies
 */
class PolynomialStrategy extends AbstractStrategy
{
    protected const DEFAULT_DEGREE = 2;

    /**
     * @var int
     */
    protected $degree;

    /**
     * PolynomialStrategy constructor.
     *
     * @param int $base
     * @param int $degree
     */
    public function __construct(int $base = self::DEFAULT_BASE, int $degree = self::DEFAULT_DEGREE)
    {
        $this->degree = $degree;
        parent::__construct($base);
    }

    /**
     * @param int $attempt
     * @return int
     */
    public function getWaitTime(int $attempt): int
    {
        $value = ($attempt ** $this->degree) * $this->base;
        return (int)$value;
    }

    /**
     * @return int
     */
    public function getDegree(): int
    {
        return $this->degree;
    }
}
