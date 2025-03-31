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

use TP\DB\Connection;
use TP\DB\ConnectionInterface;
use TP\DB\DB as DBManager;
use TP\DB\Transaction;

/**
 * @method static array               poolStats()                                                             Get connection pool statistics
 * @method static Connection          connection()                                                            Borrow a connection from the pool
 * @method static ConnectionInterface debug(\Closure $func)                                                   Debug a database operation
 * @method static ConnectionInterface raw(string $sql, ...$values)                                            Execute a raw SQL query
 * @method static ConnectionInterface exec(string $sql, ...$values)                                           Execute an SQL statement
 * @method static ConnectionInterface insert(string $table, array $data, string $insert = 'INSERT INTO')      Insert data into a table
 * @method static ConnectionInterface batchInsert(string $table, array $data, string $insert = 'INSERT INTO') Insert multiple rows into a table
 * @method static void                transaction(\Closure $closure)                                          Execute a transaction
 * @method static Transaction         beginTransaction()                                                      Start a new transaction
 * @method static ConnectionInterface table(string $table)                                                    Create a query builder for a table
 */
class DB extends Facade
{
    protected static function getFacadeAccessor(): object|string
    {
        return DBManager::class;
    }
}
