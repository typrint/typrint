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

use TP\Hook\Hook as HookManager;

/**
 * @method static void      addAction(string $tag, callable $functionToAdd, int $priority = 10, int $acceptedArgs = 1) Add an action hook
 * @method static void      addFilter(string $tag, callable $functionToAdd, int $priority = 10, int $acceptedArgs = 1) Add a filter hook
 * @method static bool      removeFilter(string $tag, callable $functionToRemove, int $priority = 10)                  Remove a filter hook
 * @method static bool|int  hasFilter(string $tag = '', callable|false $functionToCheck = false)                       Check if a filter hook exists
 * @method static bool      hasFilters()                                                                               Check if any filter hooks exist
 * @method static void      removeAllFilters(false|int $priority = false)                                              Remove all filter hooks
 * @method static mixed     applyFilter(string $tag, mixed $value, array $args = [])                                   Apply filter hooks
 * @method static void      doAction(string $tag, array $args = [])                                                    Execute action hooks
 * @method static false|int currentPriority()                                                                          Get the priority of the currently running hook
 */
class Hook extends Facade
{
    protected static function getFacadeAccessor(): object|string
    {
        return HookManager::class;
    }
}
