<?php

declare(strict_types=1);

/*
 * This file is part of TyPrint.
 *
 * (c) TyPrint Core Team <https://typrint.org>
 *
 * This source file is subject to the GNU General Public License version 3
 * that is with this source code in the file LICENSE.
 */

namespace TP\Facades;

use TP\Filesystem\FilesystemManager;

/**
 * Filesystem operations facade.
 *
 * @method static bool         exists(string $path)                                             Check if a file or directory exists
 * @method static bool         isFile(string $path)                                             Check if the path is a file
 * @method static bool         isDir(string $path)                                              Check if the path is a directory
 * @method static string|false get(string $path)                                                Get the content of a file
 * @method static bool         put(string $path, string $content)                               Put content to a file
 * @method static bool         append(string $path, string $content)                            Append content to a file
 * @method static bool         delete(string $path)                                             Delete a file
 * @method static bool         makeDir(string $path, int $mode = 0755, bool $recursive = false) Create a directory
 * @method static bool         deleteDir(string $path)                                          Delete a directory
 * @method static array        scanDir(string $path)                                            Get an array of all files in a directory
 * @method static int          size(string $path)                                               Get the file size in bytes
 * @method static int          lastModified(string $path)                                       Get the last modified time
 * @method static string       extension(string $path)                                          Get the file extension
 * @method static string       basename(string $path)                                           Get the trailing name component
 * @method static string       dirname(string $path)                                            Get the parent directory's path
 */
class Filesystem extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return FilesystemManager::class;
    }
}
