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

/**
 * Class BackoffCopyrightTest
 *
 * @package JBZoo\PHPUnit
 */
class BackoffCopyrightTest extends AbstractCopyrightTest
{
    /**
     * @var string
     */
    protected $packageName = 'Retry';

    protected $validHeaderPHP = [
        '/**',
        ' * _VENDOR_ - _PACKAGE_',
        ' *',
        ' * _DESCRIPTION_PHP_',
        ' *',
        ' * @package    _PACKAGE_',
        ' * @license    _LICENSE_',
        ' * @copyright  _COPYRIGHTS_',
        ' * @link       _LINK_',
        ' */',
        '',
        'declare(strict_types=1);',
        ''
    ];
}
