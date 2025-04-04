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

use TP\Log\LogManager;

/**
 * Log operations facade.
 *
 * @method static void emergency(string $message, array $context = [])          System is unusable
 * @method static void alert(string $message, array $context = [])              Action must be taken immediately
 * @method static void critical(string $message, array $context = [])           Critical conditions
 * @method static void error(string $message, array $context = [])              Error conditions
 * @method static void warning(string $message, array $context = [])            Warning conditions
 * @method static void notice(string $message, array $context = [])             Normal but significant events
 * @method static void info(string $message, array $context = [])               Interesting events
 * @method static void debug(string $message, array $context = [])              Detailed debug information
 * @method static void log(string $level, string $message, array $context = []) Log with arbitrary level
 */
class Log extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return LogManager::class;
    }
}
