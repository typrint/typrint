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

use TP\Facades\Filesystem;
use TP\Facades\Log;

/**
 * Plugin loader for TyPrint.
 *
 * Manages the discovery, metadata extraction, and loading of plugins
 * from the tp-content/plugins directory.
 */
class PluginLoader
{
    /**
     * The singleton instance of the plugin loader.
     */
    protected static ?self $instance = null;

    /**
     * The directory where plugins are stored.
     */
    protected string $pluginsDir = ABSPATH.'/tp-content/plugins';

    /**
     * Array of loaded plugins.
     *
     * @var Plugin[]
     */
    protected array $plugins = [];

    /**
     * Required header fields for plugins.
     */
    protected array $headerFields = [
        'Plugin Name' => 'name',
        'Plugin URI' => 'uri',
        'Description' => 'description',
        'Version' => 'version',
        'Author' => 'author',
        'Author URI' => 'authorUri',
        'License' => 'license',
        'License URI' => 'licenseUri',
        'Text Domain' => 'textDomain',
        'Domain Path' => 'domainPath',
        'Requires TyPrint' => 'requiresTyPrint',
        'Requires PHP' => 'requiresPhp',
    ];

    /**
     * Get the singleton instance of the plugin loader.
     */
    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize the plugin loader.
     */
    protected function __construct()
    {
        $this->discoverPlugins();
    }

    /**
     * Discover all plugins in the plugins directory.
     */
    public function discoverPlugins(): void
    {
        if (!Filesystem::isDir($this->pluginsDir)) {
            Log::warning('Plugins directory does not exist');

            return;
        }

        // Get all items in the plugins directory
        $items = Filesystem::scanDir($this->pluginsDir);

        foreach ($items as $item) {
            $pluginPath = $this->pluginsDir.'/'.$item;

            // Handle single-file plugins
            if (Filesystem::isFile($pluginPath) && $this->hasPluginExtension($pluginPath)) {
                $this->processPluginFile($pluginPath);
                continue;
            }

            // Handle directory plugins
            if (Filesystem::isDir($pluginPath)) {
                $this->processPluginDirectory($pluginPath, $item);
            }
        }
    }

    /**
     * Check if a file has a valid plugin extension (.php).
     *
     * @param string $filePath The file path to check
     *
     * @return bool Whether the file has a valid plugin extension
     */
    protected function hasPluginExtension(string $filePath): bool
    {
        return 'php' === pathinfo($filePath, PATHINFO_EXTENSION);
    }

    /**
     * Process a single-file plugin.
     *
     * @param string $filePath The path to the plugin file
     */
    protected function processPluginFile(string $filePath): void
    {
        $pluginData = $this->extractPluginData($filePath);
        if (!empty($pluginData['name'])) {
            $plugin = new Plugin(
                basename($filePath),
                $filePath,
                false,
                $pluginData
            );
            $this->plugins[$plugin->getSlug()] = $plugin;
        }
    }

    /**
     * Process a plugin directory.
     *
     * @param string $dirPath The path to the plugin directory
     * @param string $dirName The directory name
     */
    protected function processPluginDirectory(string $dirPath, string $dirName): void
    {
        // Look for the main plugin file (same name as directory or index.php)
        $mainFile = $dirPath.'/'.$dirName.'.php';

        if (!Filesystem::isFile($mainFile)) {
            $mainFile = $dirPath.'/index.php';
            if (!Filesystem::isFile($mainFile)) {
                return; // No valid main plugin file found
            }
        }

        $pluginData = $this->extractPluginData($mainFile);
        if (!empty($pluginData['name'])) {
            $plugin = new Plugin(
                $dirName,
                $mainFile,
                true,
                $pluginData
            );
            $this->plugins[$plugin->getSlug()] = $plugin;
        }
    }

    /**
     * Extract plugin metadata from a file.
     *
     * @param string $filePath The path to the plugin file
     *
     * @return array The plugin metadata
     */
    protected function extractPluginData(string $filePath): array
    {
        $defaultHeaders = array_fill_keys(array_values($this->headerFields), '');

        // Get the plugin file contents
        $content = Filesystem::get($filePath);
        if (empty($content)) {
            return $defaultHeaders;
        }

        // WordPress-style plugin data extraction
        if (!preg_match('|/\*\s*(.*?)\s*\*/|s', $content, $matches)) {
            return $defaultHeaders;
        }

        $headers = $matches[1];
        $data = $defaultHeaders;

        foreach ($this->headerFields as $field => $key) {
            if (preg_match('|'.preg_quote($field, '|').':\s*(.*)$|mi', $headers, $match) && $match[1]) {
                $data[$key] = trim($match[1]);
            }
        }

        return $data;
    }

    /**
     * Get all discovered plugins.
     *
     * @return Plugin[] Array of plugins
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * Get a specific plugin by slug.
     *
     * @param string $slug The plugin slug
     *
     * @return Plugin|null The plugin or null if not found
     */
    public function getPlugin(string $slug): ?Plugin
    {
        return $this->plugins[$slug] ?? null;
    }

    /**
     * Check if a plugin exists.
     *
     * @param string $slug The plugin slug
     *
     * @return bool Whether the plugin exists
     */
    public function hasPlugin(string $slug): bool
    {
        return isset($this->plugins[$slug]);
    }

    /**
     * Get the number of discovered plugins.
     *
     * @return int The number of plugins
     */
    public function count(): int
    {
        return count($this->plugins);
    }
}
