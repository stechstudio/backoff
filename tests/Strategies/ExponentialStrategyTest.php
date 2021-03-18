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

use JBZoo\Retry\Strategies\ExponentialStrategy;
use PHPUnit\Framework\TestCase;

/**
 * Class ExponentialStrategyTest
 * @package JBZoo\PHPUnit
 */
class ExponentialStrategyTest extends TestCase
{
    public function testDefaults()
    {
        $strategy = new ExponentialStrategy();

        isSame(100, $strategy->getBase());
    }

    public function testWaitTimes()
    {
        $s = new ExponentialStrategy(200);

        isSame(200, $s->getWaitTime(1));
        isSame(800, $s->getWaitTime(2));
        isSame(1600, $s->getWaitTime(3));
        isSame(3200, $s->getWaitTime(4));
        isSame(6400, $s->getWaitTime(5));
        isSame(12800, $s->getWaitTime(6));
        isSame(25600, $s->getWaitTime(7));
        isSame(51200, $s->getWaitTime(8));
    }
}
