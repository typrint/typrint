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

namespace TP\Cache;

use Psr\SimpleCache\CacheInterface;
use TP\Cache\Handler\HandlerInterface;
use TP\Cache\Handler\MemoryHandler;
use TP\Utils\Once;

// TODO support cache group like WordPress

class Cache implements CacheInterface
{
    /**
     * The cache instance.
     */
    private static Cache $instance;

    private static Once $once;

    /**
     * The cache handler, plugin can override it.
     */
    public HandlerInterface $handler;

    public static function init(): void
    {
        self::$once = new Once();
    }

    public static function instance(): self
    {
        if (!isset(self::$instance)) {
            self::$once->do(fn () => self::$instance = new self(new MemoryHandler()));
        }

        return self::$instance;
    }

    /**
     * Cache constructor.
     */
    public function __construct(HandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Get a value from the cache.
     *
     * @param null $default
     */
    public function get($key, $default = null): mixed
    {
        return $this->handler->get($key, $default);
    }

    /**
     * Set a value in the cache.
     *
     * @param null $ttl
     */
    public function set($key, $value, $ttl = null): bool
    {
        return $this->handler->set($key, $value, $ttl);
    }

    /**
     * Delete a value from the cache.
     */
    public function delete($key): bool
    {
        return $this->handler->delete($key);
    }

    /**
     * Clear the entire cache.
     */
    public function clear(): bool
    {
        return $this->handler->clear();
    }

    /**
     * Check if a value exists in the cache.
     */
    public function has($key): bool
    {
        return $this->handler->has($key);
    }

    /**
     * Multiple get values from the cache.
     *
     * @param null $default
     *
     * @return array
     */
    public function getMultiple($keys, $default = null): iterable
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $default);
        }

        return $results;
    }

    /**
     * Multiple set values in the cache.
     *
     * @param null $ttl
     */
    public function setMultiple($values, $ttl = null): bool
    {
        $results = [];
        foreach ($values as $key => $value) {
            $results[] = $this->set($key, $value, $ttl);
        }
        foreach ($results as $result) {
            if (!$result) {
                return false;
            }
        }

        return true;
    }

    /**
     * Multiple delete values from the cache.
     */
    public function deleteMultiple($keys): bool
    {
        $results = [];
        foreach ($keys as $key) {
            $results[] = $this->delete($key);
        }
        foreach ($results as $result) {
            if (!$result) {
                return false;
            }
        }

        return true;
    }
}
