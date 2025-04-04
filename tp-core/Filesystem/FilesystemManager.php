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

namespace TP\Filesystem;

/**
 * Filesystem manager for TyPrint.
 *
 * Provides file system operations optimized for Swow environment.
 */
class FilesystemManager
{
    /**
     * The singleton instance of the manager.
     */
    protected static ?self $instance = null;

    /**
     * Get the singleton instance of the file system manager.
     */
    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Check if a file or directory exists.
     */
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Check if the path is a file.
     */
    public function isFile(string $path): bool
    {
        return is_file($path);
    }

    /**
     * Check if the path is a directory.
     */
    public function isDir(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * Get the content of a file.
     */
    public function get(string $path): string|false
    {
        if (!$this->isFile($path)) {
            return false;
        }

        return file_get_contents($path);
    }

    /**
     * Put content to a file.
     */
    public function put(string $path, string $content): bool
    {
        $directory = dirname($path);

        if (!$this->isDir($directory)) {
            $this->makeDir($directory, 0o755, true);
        }

        return false !== file_put_contents($path, $content);
    }

    /**
     * Append content to a file.
     */
    public function append(string $path, string $content): bool
    {
        return false !== file_put_contents($path, $content, FILE_APPEND);
    }

    /**
     * Delete a file.
     */
    public function delete(string $path): bool
    {
        if (!$this->isFile($path)) {
            return false;
        }

        return unlink($path);
    }

    /**
     * Create a directory.
     */
    public function makeDir(string $path, int $mode = 0o755, bool $recursive = false): bool
    {
        if ($this->isDir($path)) {
            return true;
        }

        return mkdir($path, $mode, $recursive);
    }

    /**
     * Delete a directory.
     */
    public function deleteDir(string $path): bool
    {
        if (!$this->isDir($path)) {
            return false;
        }

        $items = $this->scanDir($path);
        foreach ($items as $item) {
            $itemPath = $path.'/'.$item;

            if ('.' !== $item && '..' !== $item) {
                if ($this->isDir($itemPath)) {
                    $this->deleteDir($itemPath);
                } else {
                    $this->delete($itemPath);
                }
            }
        }

        return rmdir($path);
    }

    /**
     * Get an array of all files in a directory.
     */
    public function scanDir(string $path): array
    {
        if (!$this->isDir($path)) {
            return [];
        }

        $result = scandir($path);

        return false !== $result ? $result : [];
    }

    /**
     * Get the file size in bytes.
     */
    public function size(string $path): int
    {
        if (!$this->isFile($path)) {
            return 0;
        }

        return filesize($path) ?: 0;
    }

    /**
     * Get the last modified time.
     */
    public function lastModified(string $path): int
    {
        if (!$this->exists($path)) {
            return 0;
        }

        return filemtime($path) ?: 0;
    }

    /**
     * Get the file extension.
     */
    public function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Get the trailing name component.
     */
    public function basename(string $path): string
    {
        return basename($path);
    }

    /**
     * Get the parent directory's path.
     */
    public function dirname(string $path): string
    {
        return dirname($path);
    }
}
