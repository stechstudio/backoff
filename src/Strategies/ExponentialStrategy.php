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
 * Class ExponentialStrategy
 * @package JBZoo\Retry\Strategies
 */
class ExponentialStrategy extends AbstractStrategy
{
    /**
     * @param int $attempt
     *
     * @return int
     */
    public function getWaitTime(int $attempt): int
    {
        return $attempt === 1 ? $this->base : (2 ** $attempt) * $this->base;
    }
}
