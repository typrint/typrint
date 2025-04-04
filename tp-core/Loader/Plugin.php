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

namespace TP\Loader;

/**
 * Represents a single plugin in the TyPrint system.
 */
class Plugin
{
    /**
     * Plugin slug (directory name or filename without extension).
     */
    protected string $slug;

    /**
     * Plugin main file path.
     */
    protected string $file;

    /**
     * Whether the plugin is a directory plugin.
     */
    protected bool $isDirectory;

    /**
     * Plugin metadata.
     */
    protected array $data;

    /**
     * Whether the plugin is active.
     */
    protected bool $active = false;

    /**
     * Create a new Plugin instance.
     *
     * @param string $slug        Plugin slug
     * @param string $file        Main plugin file
     * @param bool   $isDirectory Whether the plugin is a directory plugin
     * @param array  $data        Plugin metadata
     */
    public function __construct(string $slug, string $file, bool $isDirectory, array $data)
    {
        $this->slug = $slug;
        $this->file = $file;
        $this->isDirectory = $isDirectory;
        $this->data = $data;
    }

    /**
     * Get the plugin slug.
     *
     * @return string The plugin slug
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Get the plugin main file.
     *
     * @return string The plugin main file
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * Get the plugin name.
     *
     * @return string The plugin name
     */
    public function getName(): string
    {
        return $this->data['name'] ?? $this->slug;
    }

    /**
     * Get the plugin version.
     *
     * @return string The plugin version
     */
    public function getVersion(): string
    {
        return $this->data['version'] ?? '';
    }

    /**
     * Get the plugin description.
     *
     * @return string The plugin description
     */
    public function getDescription(): string
    {
        return $this->data['description'] ?? '';
    }

    /**
     * Get the plugin author.
     *
     * @return string The plugin author
     */
    public function getAuthor(): string
    {
        return $this->data['author'] ?? '';
    }

    /**
     * Get the plugin metadata.
     *
     * @return array The plugin metadata
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Check if the plugin is a directory plugin.
     *
     * @return bool Whether the plugin is a directory plugin
     */
    public function isDirectory(): bool
    {
        return $this->isDirectory;
    }

    /**
     * Check if the plugin is active.
     *
     * @return bool Whether the plugin is active
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Set the plugin active status.
     *
     * @param bool $active Whether the plugin is active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }
}
