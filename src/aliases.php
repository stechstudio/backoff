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

// phpcs:ignoreFile

use JBZoo\Retry\Retry;
use JBZoo\Retry\Strategies\ConstantStrategy;
use JBZoo\Retry\Strategies\ExponentialStrategy;
use JBZoo\Retry\Strategies\LinearStrategy;
use JBZoo\Retry\Strategies\PolynomialStrategy;

use function JBZoo\Retry\retry;

if (!function_exists('backoff')) {
    /**
     * @return mixed
     */
    function backoff()
    {
        return retry(...func_get_args());
    }
}

// @phan-suppress-next-line PhanUndeclaredClassReference
if (!class_exists(STS\Backoff\Backoff::class)) {
    \class_alias(Retry::class, 'STS\Backoff\Backoff');
    \class_alias(ConstantStrategy::class, 'STS\Backoff\Strategies\ConstantStrategy');
    \class_alias(ExponentialStrategy::class, 'STS\Backoff\Strategies\ExponentialStrategy');
    \class_alias(LinearStrategy::class, 'STS\Backoff\Strategies\LinearStrategy');
    \class_alias(PolynomialStrategy::class, 'STS\Backoff\Backoff\PolynomialStrategy');
}
