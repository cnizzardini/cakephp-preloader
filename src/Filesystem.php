<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace CakePreloader;

use CallbackFilterIterator;
use FilesystemIterator;
use Iterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

/**
 * The Filesystem class was deprecated in CakePHP 4.x and removed in CakePHP 5.x but this library relies on it
 * until a suitable replacement can be introduced. All unnecessary methods have been removed from this copy.
 *
 * @internal
 */
final class Filesystem
{
    /**
     * Directory type constant
     *
     * @var string
     */
    public const TYPE_DIR = 'dir';

    /**
     * Find files / directories (non-recursively) in given directory path.
     *
     * @param string $path Directory path.
     * @param mixed $filter If string will be used as regex for filtering using
     *   `RegexIterator`, if callable will be as callback for `CallbackFilterIterator`.
     * @param int|null $flags Flags for FilesystemIterator::__construct();
     * @return \Iterator
     */
    public function find(string $path, mixed $filter = null, ?int $flags = null): Iterator
    {
        $flags = $flags ?? FilesystemIterator::KEY_AS_PATHNAME
        | FilesystemIterator::CURRENT_AS_FILEINFO
        | FilesystemIterator::SKIP_DOTS;
        $directory = new FilesystemIterator($path, $flags);

        if ($filter === null) {
            return $directory;
        }

        return $this->filterIterator($directory, $filter);
    }

    /**
     * Find files/ directories recursively in given directory path.
     *
     * @param string $path Directory path.
     * @param mixed $filter If string will be used as regex for filtering using
     *   `RegexIterator`, if callable will be as callback for `CallbackFilterIterator`.
     *   Hidden directories (starting with dot e.g. .git) are always skipped.
     * @param int|null $flags Flags for FilesystemIterator::__construct();
     * @return \Iterator
     */
    public function findRecursive(string $path, mixed $filter = null, ?int $flags = null): Iterator
    {
        $flags = $flags ?? FilesystemIterator::KEY_AS_PATHNAME
        | FilesystemIterator::CURRENT_AS_FILEINFO
        | FilesystemIterator::SKIP_DOTS;
        $directory = new RecursiveDirectoryIterator($path, $flags);

        $dirFilter = new RecursiveCallbackFilterIterator(
            $directory,
            function (SplFileInfo $current) {
                if ($current->getFilename()[0] === '.' && $current->isDir()) {
                    return false;
                }

                return true;
            }
        );

        $flatten = new RecursiveIteratorIterator(
            $dirFilter,
            RecursiveIteratorIterator::CHILD_FIRST
        );

        if ($filter === null) {
            return $flatten;
        }

        return $this->filterIterator($flatten, $filter);
    }

    /**
     * Wrap iterator in additional filtering iterator.
     *
     * @param \Iterator $iterator Iterator
     * @param mixed $filter Regex string or callback.
     * @return \Iterator
     */
    protected function filterIterator(Iterator $iterator, mixed $filter): Iterator
    {
        if (is_string($filter)) {
            return new RegexIterator($iterator, $filter);
        }

        return new CallbackFilterIterator($iterator, $filter);
    }
}
