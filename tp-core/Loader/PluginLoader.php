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
use TP\Facades\Hook;
use TP\Facades\Log;

/**
 * Plugin loader for TyPrint.
 *
 * Manages the discovery, metadata extraction, and loading of plugins.
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
    protected string $pluginsDir = TP_CONTENT_DIR.'/plugins';

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
        // 'Domain Path' => 'domainPath',
        'Network' => 'network',
        'Requires at least' => 'requiresTyPrint',
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
        $this->discover();
        $this->load();
    }

    /**
     * Discover all plugins in the plugins directory.
     */
    public function discover(): void
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
            if (Filesystem::isFile($pluginPath) && 'php' === pathinfo($pluginPath, PATHINFO_EXTENSION)) {
                $this->processFile($pluginPath);
                continue;
            }

            // Handle directory plugins
            if (Filesystem::isDir($pluginPath)) {
                $this->processDirectory($pluginPath, $item);
            }
        }
    }

    /**
     * Process a single plugin file.
     *
     * @param string $filePath The path to the plugin file
     */
    protected function processFile(string $filePath): void
    {
        $pluginData = $this->parseData($filePath);
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
    protected function processDirectory(string $dirPath, string $dirName): void
    {
        // Look for the main plugin file (same name as directory or index.php)
        $mainFile = $dirPath.'/'.$dirName.'.php';

        if (!Filesystem::isFile($mainFile)) {
            $mainFile = $dirPath.'/index.php';
            if (!Filesystem::isFile($mainFile)) {
                return; // No valid main plugin file found
            }
        }

        $pluginData = $this->parseData($mainFile);
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
     * Parse plugin metadata from a file.
     *
     * @param string $filePath The path to the plugin file
     *
     * @return array The plugin metadata
     */
    protected function parseData(string $filePath): array
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
     * Get a specific plugin by slug.
     *
     * @param string $slug The plugin slug
     *
     * @return Plugin|null The plugin or null if not found
     */
    public function get(string $slug): ?Plugin
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
    public function has(string $slug): bool
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

    /**
     * Get all loaded plugins.
     *
     * @return iterable An iterable of loaded plugins
     */
    public function all(): iterable
    {
        yield from $this->plugins;
    }

    /**
     * Load plugins.
     */
    protected function load(): void
    {
        foreach ($this->all() as $plugin) {
            if ($plugin->isActive()) {
                Hook::doAction('before_load_plugin', $plugin);
                include_once $plugin->getFile();
                Hook::doAction('after_load_plugin', $plugin);
            }
        }
    }
}
