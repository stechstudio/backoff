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

use function JBZoo\Data\json;

/**
 * Class RetryComposerTest
 * @package JBZoo\PHPUnit
 */
class RetryComposerTest extends AbstractComposerTest
{
    public function testAuthor(): void
    {
        $composerPath = PROJECT_ROOT . '/composer.json';
        $composerJson = json($composerPath);

        if ($this->authorName) {
            isSame($this->authorName, $composerJson->find('authors.1.name'), "See file: {$composerPath}");
        }

        if ($this->authorEmail) {
            isSame($this->authorEmail, $composerJson->find('authors.1.email'), "See file: {$composerPath}");
        }
    }
}
