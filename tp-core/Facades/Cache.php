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

use TP\Cache\Cache as CacheManager;

/**
 * @method static mixed    get($key, $default = null)          Get a value from the cache
 * @method static bool     set($key, $value, $ttl = null)      Set a value in the cache
 * @method static bool     delete($key)                        Delete a value from the cache
 * @method static bool     clear()                             Clear the entire cache
 * @method static bool     has($key)                           Check if a value exists in the cache
 * @method static iterable getMultiple($keys, $default = null) Get multiple values from the cache
 * @method static bool     setMultiple($values, $ttl = null)   Set multiple values in the cache
 * @method static bool     deleteMultiple($keys)               Delete multiple values from the cache
 */
class Cache extends Facade
{
    protected static function getFacadeAccessor(): object|string
    {
        return CacheManager::class;
    }
}
