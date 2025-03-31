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

use TP\DB\DB as DBManager;

class DB extends Facade
{
    protected static function getFacadeAccessor(): object|string
    {
        return DBManager::class;
    }
}
