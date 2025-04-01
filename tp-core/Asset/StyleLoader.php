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

class StyleLoader
{
    private static StyleLoader $instance;

    private static Once $once;

    /**
     * Holds the registered styles.
     *
     * @var array<string, array>
     */
    private array $registered = [];

    /**
     * Holds queued styles.
     *
     * @var array<string, bool>
     */
    private array $enqueued = [];

    /**
     * Holds done styles.
     *
     * @var array<string, bool>
     */
    private array $done = [];

    /**
     * Base URL for styles.
     */
    private string $baseUrl = '';

    /**
     * Returns the singleton instance of this class.
     *
     * @return self the StyleLoader instance
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
     * Sets the base URL for styles.
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
     * Registers a stylesheet.
     *
     * @param string           $handle Name of the stylesheet. Should be unique.
     * @param string           $src    full URL of the stylesheet, or path of the stylesheet relative to the base URL
     * @param array            $deps   array of handles of stylesheets this stylesheet depends on
     * @param string|bool|null $ver    string specifying the stylesheet version number, or false/null if no version
     * @param string           $media  Optional. The media for which this stylesheet has been defined.
     *
     * @return bool true on success, false if stylesheet has already been registered
     *
     * @since 1.0.0
     */
    public function register(string $handle, string $src, array $deps = [], string|bool|null $ver = null, string $media = 'all'): bool
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
            'media' => $media,
            'inline' => [],
            'extra' => [],
            'priority' => 10,
        ];

        return true;
    }

    /**
     * Adds inline CSS styles to a registered stylesheet.
     *
     * @param string $handle   the style handle to add the inline styles to
     * @param string $css      the CSS styles to be added
     * @param string $position Optional. Whether to add the inline styles before or after the stylesheet.
     *                         Accepts 'before' or 'after'. Default 'after'.
     *
     * @return bool true on success, false if the style doesn't exist
     *
     * @since 1.0.0
     */
    public function addInline(string $handle, string $css, string $position = 'after'): bool
    {
        if (!isset($this->registered[$handle])) {
            return false;
        }

        if (!in_array($position, ['before', 'after'])) {
            $position = 'after';
        }

        $this->registered[$handle]['inline'][$position][] = $css;

        return true;
    }

    /**
     * Adds extra attributes for a registered stylesheet.
     *
     * @param string $handle the style handle to add attributes for
     * @param string $key    the attribute name
     * @param mixed  $value  the attribute value
     *
     * @return bool true on success, false if the style doesn't exist
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
     * Sets the priority for a registered stylesheet.
     *
     * @param string $handle   the style handle
     * @param int    $priority the priority (default: 10, lower = earlier output)
     *
     * @return bool true on success, false if the style doesn't exist
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
     * Marks a registered stylesheet to be enqueued.
     *
     * @param string $handle the style handle to enqueue
     *
     * @return bool true on success, false if the style doesn't exist
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
     * Generates HTML for all enqueued styles.
     *
     * @return string HTML for the styles
     *
     * @since 1.0.0
     */
    public function getHtml(): string
    {
        $html = '';

        // Sort by priority
        $styles = [];
        foreach ($this->enqueued as $handle => $value) {
            if ($value && isset($this->registered[$handle]) && !isset($this->done[$handle])) {
                $styles[$handle] = $this->registered[$handle]['priority'];
                $this->done[$handle] = true;
            }
        }

        asort($styles);

        foreach (array_keys($styles) as $handle) {
            $style = $this->registered[$handle];

            // Handle dependencies
            foreach ($style['deps'] as $dep) {
                if (!isset($this->done[$dep])) {
                    // Dependency not done, skip this style
                    unset($this->done[$handle]);
                    continue 2;
                }
            }

            $html .= $this->generateStyleTag($handle);
        }

        return $html;
    }

    /**
     * Generates HTML for a single style tag.
     *
     * @param string $handle the style handle
     *
     * @return string HTML for the style tag
     *
     * @since 1.0.0
     */
    private function generateStyleTag(string $handle): string
    {
        $style = $this->registered[$handle];
        $tag = '';

        // Add inline styles before the link tag
        if (!empty($style['inline']['before'])) {
            $before = implode("\n", $style['inline']['before']);
            if ($before) {
                $tag .= '<style id="'.$handle.'-inline-css-before">'.$before.'</style>'."\n";
            }
        }

        // Add stylesheet link
        if ($style['src']) {
            $src = $style['src'];
            if (null !== $style['ver'] && false !== $style['ver']) {
                $src = add_query_arg('ver', (string) $style['ver'], $src);
            }

            $tag .= '<link rel="stylesheet" id="'.$handle.'-css" href="'.esc_attr($src).'"';

            if ($style['media']) {
                $tag .= ' media="'.esc_attr($style['media']).'"';
            }

            // Add extra attributes
            foreach ($style['extra'] as $key => $value) {
                if (true === $value) {
                    $tag .= ' '.$key;
                } else {
                    $tag .= ' '.$key.'="'.esc_attr((string) $value).'"';
                }
            }

            $tag .= '>'."\n";
        }

        // Add inline styles after the link tag
        if (!empty($style['inline']['after'])) {
            $after = implode("\n", $style['inline']['after']);
            if ($after) {
                $tag .= '<style id="'.$handle.'-inline-css-after">'.$after.'</style>'."\n";
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
     * Checks whether a style has been registered.
     *
     * @param string $handle the style handle to check
     *
     * @return bool whether the style is registered
     *
     * @since 1.0.0
     */
    public function isRegistered(string $handle): bool
    {
        return isset($this->registered[$handle]);
    }

    /**
     * Checks whether a style has been enqueued.
     *
     * @param string $handle the style handle to check
     *
     * @return bool whether the style is enqueued
     *
     * @since 1.0.0
     */
    public function isEnqueued(string $handle): bool
    {
        return isset($this->enqueued[$handle]);
    }

    /**
     * Checks whether a style has been marked as done.
     *
     * @param string $handle the style handle to check
     *
     * @return bool whether the style is marked as done
     *
     * @since 1.0.0
     */
    public function isDone(string $handle): bool
    {
        return isset($this->done[$handle]);
    }
}
