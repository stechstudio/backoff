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

/**
 * @param \Closure $callback
 * @param int      $maxAttempts
 * @param mixed    $strategy
 * @param int      $waitCap
 * @param bool     $useJitter
 * @return mixed|null
 * @throws \Exception
 */
function retry(
    \Closure $callback,
    int $maxAttempts = Retry::DEFAULT_MAX_ATTEMPTS,
    $strategy = Retry::DEFAULT_STRATEGY,
    int $waitCap = Retry::DEFAULT_WAIT_CAP,
    bool $useJitter = Retry::DEFAULT_JITTER_STATE
) {
    return (new Retry($maxAttempts, $strategy, $waitCap, $useJitter))->run($callback);
}
