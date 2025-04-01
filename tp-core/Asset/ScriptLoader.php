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

namespace TP\Asset;

use TP\Utils\Once;

class ScriptLoader
{
    private static ScriptLoader $instance;

    private static Once $once;

    /**
     * Holds the registered scripts.
     *
     * @var array<string, array>
     */
    private array $registered = [];

    /**
     * Holds queued scripts.
     *
     * @var array<string, bool>
     */
    private array $enqueued = [];

    /**
     * Holds done scripts.
     *
     * @var array<string, bool>
     */
    private array $done = [];

    /**
     * Base URL for scripts.
     */
    private string $baseUrl = '';

    /**
     * Returns the singleton instance of this class.
     *
     * @return self the ScriptLoader instance
     *
     * @since 1.0.0
     */
    public static function instance(): self
    {
        if (!isset(self::$instance)) {
            self::$once ??= new Once();
            self::$once->do(fn () => self::$instance = new self());
        }

        return self::$instance;
    }

    /**
     * Sets the base URL for scripts.
     *
     * @param string $url the base URL
     *
     * @return self for method chaining
     *
     * @since 1.0.0
     */
    public function setBaseUrl(string $url): self
    {
        $this->baseUrl = rtrim($url, '/');

        return $this;
    }

    /**
     * Registers a script.
     *
     * @param string           $handle   Name of the script. Should be unique.
     * @param string           $src      full URL of the script, or path of the script relative to the base URL
     * @param array            $deps     array of handles of scripts this script depends on
     * @param string|bool|null $ver      string specifying the script version number, or false/null if no version
     * @param bool             $inFooter whether to enqueue the script before </body> instead of in the <head>
     *
     * @return bool true on success, false if script has already been registered
     *
     * @since 1.0.0
     */
    public function register(string $handle, string $src, array $deps = [], string|bool|null $ver = null, bool $inFooter = false): bool
    {
        if (isset($this->registered[$handle])) {
            return false;
        }

        if ($src && !str_starts_with($src, 'http://') && !str_starts_with($src, 'https://') && !str_starts_with($src, '//')) {
            $src = $this->baseUrl.'/'.ltrim($src, '/');
        }

        $this->registered[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'in_footer' => $inFooter,
            'data' => [],
            'extra' => [],
            'priority' => 10,
        ];

        return true;
    }

    /**
     * Adds inline data for a script.
     *
     * @param string $handle the script handle to add data for
     * @param string $key    the data variable name
     * @param mixed  $value  the data value
     *
     * @return bool true on success, false if the script doesn't exist
     *
     * @since 1.0.0
     */
    public function addData(string $handle, string $key, mixed $value): bool
    {
        if (!isset($this->registered[$handle])) {
            return false;
        }

        $this->registered[$handle]['data'][$key] = $value;

        return true;
    }

    /**
     * Adds extra attributes for a registered script.
     *
     * @param string $handle the script handle to add attributes for
     * @param string $key    the attribute name
     * @param mixed  $value  the attribute value
     *
     * @return bool true on success, false if the script doesn't exist
     *
     * @since 1.0.0
     */
    public function addExtra(string $handle, string $key, mixed $value): bool
    {
        if (!isset($this->registered[$handle])) {
            return false;
        }

        $this->registered[$handle]['extra'][$key] = $value;

        return true;
    }

    /**
     * Sets the priority for a registered script.
     *
     * @param string $handle   the script handle
     * @param int    $priority the priority (default: 10, lower = earlier output)
     *
     * @return bool true on success, false if the script doesn't exist
     *
     * @since 1.0.0
     */
    public function setPriority(string $handle, int $priority): bool
    {
        if (!isset($this->registered[$handle])) {
            return false;
        }

        $this->registered[$handle]['priority'] = $priority;

        return true;
    }

    /**
     * Marks a registered script to be enqueued.
     *
     * @param string $handle the script handle to enqueue
     *
     * @return bool true on success, false if the script doesn't exist
     *
     * @since 1.0.0
     */
    public function enqueue(string $handle): bool
    {
        if (isset($this->enqueued[$handle])) {
            return true;
        }

        if (!isset($this->registered[$handle])) {
            return false;
        }

        $this->enqueued[$handle] = true;

        // Enqueue dependencies
        foreach ($this->registered[$handle]['deps'] as $dep) {
            $this->enqueue($dep);
        }

        return true;
    }

    /**
     * Generates HTML for all enqueued scripts.
     *
     * @param bool $inFooter whether to display scripts enqueued to be displayed in the footer
     *
     * @return string HTML for the scripts
     *
     * @since 1.0.0
     */
    public function getHtml(bool $inFooter = false): string
    {
        $html = '';

        // Sort by priority
        $scripts = [];
        foreach ($this->enqueued as $handle => $value) {
            if ($value && isset($this->registered[$handle]) && !isset($this->done[$handle])
                && $this->registered[$handle]['in_footer'] === $inFooter) {
                $scripts[$handle] = $this->registered[$handle]['priority'];
                $this->done[$handle] = true;
            }
        }

        asort($scripts);

        foreach (array_keys($scripts) as $handle) {
            $script = $this->registered[$handle];

            // Handle dependencies
            foreach ($script['deps'] as $dep) {
                if (!isset($this->done[$dep])) {
                    // Dependency not done, skip this script
                    unset($this->done[$handle]);
                    continue 2;
                }
            }

            $html .= $this->generateScriptTag($handle);
        }

        return $html;
    }

    /**
     * Generates HTML for a single script tag.
     *
     * @param string $handle the script handle
     *
     * @return string HTML for the script tag
     *
     * @since 1.0.0
     */
    private function generateScriptTag(string $handle): string
    {
        $script = $this->registered[$handle];
        $tag = '';

        if ($script['src']) {
            $src = $script['src'];
            if (null !== $script['ver'] && false !== $script['ver']) {
                $src = add_query_arg('ver', (string) $script['ver'], $src);
            }

            $tag = '<script src="'.esc_attr($src).'"';

            // Add extra attributes
            foreach ($script['extra'] as $key => $value) {
                if (true === $value) {
                    $tag .= ' '.$key;
                } else {
                    $tag .= ' '.$key.'="'.esc_attr((string) $value).'"';
                }
            }

            $tag .= '></script>'."\n";
        }

        // Add inline data
        if (!empty($script['data'])) {
            $data = '';
            foreach ($script['data'] as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $data .= 'const '.$key.' = '.$value.';'."\n";
            }

            if ($data) {
                $tag .= '<script>'.$data.'</script>'."\n";
            }
        }

        return $tag;
    }

    /**
     * Resets the class properties to empty.
     *
     * @since 1.0.0
     */
    public function reset(): void
    {
        $this->registered = [];
        $this->enqueued = [];
        $this->done = [];
    }

    /**
     * Checks whether a script has been registered.
     *
     * @param string $handle the script handle to check
     *
     * @return bool whether the script is registered
     *
     * @since 1.0.0
     */
    public function isRegistered(string $handle): bool
    {
        return isset($this->registered[$handle]);
    }

    /**
     * Checks whether a script has been enqueued.
     *
     * @param string $handle the script handle to check
     *
     * @return bool whether the script is enqueued
     *
     * @since 1.0.0
     */
    public function isEnqueued(string $handle): bool
    {
        return isset($this->enqueued[$handle]);
    }

    /**
     * Checks whether a script has been marked as done.
     *
     * @param string $handle the script handle to check
     *
     * @return bool whether the script is marked as done
     *
     * @since 1.0.0
     */
    public function isDone(string $handle): bool
    {
        return isset($this->done[$handle]);
    }
}
