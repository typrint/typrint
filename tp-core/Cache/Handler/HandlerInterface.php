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

namespace TP\Cache\Handler;

interface HandlerInterface
{
    /**
     * Get a value from the cache.
     *
     * @param null $default
     */
    public function get($key, $default = null);

    /**
     * Set a value in the cache.
     *
     * @param null $ttl
     */
    public function set($key, $value, $ttl = null): bool;

    /**
     * Delete a value from the cache.
     */
    public function delete($key): bool;

    /**
     * Clear the entire cache.
     */
    public function clear(): bool;

    /**
     * Check if a value exists in the cache.
     */
    public function has($key): bool;
}
