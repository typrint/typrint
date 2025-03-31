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

abstract class Facade
{
    public static function getFacadeInstance()
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    protected static function resolveFacadeInstance(object|string $name): mixed
    {
        return $name::instance();
    }

    protected static function getFacadeAccessor(): object|string
    {
        throw new \RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    public static function __callStatic(string $method, array $args)
    {
        $instance = static::getFacadeInstance();

        if (!$instance) {
            throw new \RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }
}
