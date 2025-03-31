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

namespace TP\DB\Migrator;

use Phinx\Config\Config;
use Phinx\Migration\Manager;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use TP\DB\DB;

class Migrator
{
    public static function run(): void
    {
        $connection = DB::instance()->connection();
        $config = new Config([
            'paths' => [
                'migrations' => ABSPATH.'/tp-core/DB/Migration/migrations',
                'seeds' => ABSPATH.'/tp-core/DB/Migration/seeds',
            ],
            'environments' => [
                'default_migration_table' => DB_TABLE_PREFIX.'migrations',
                'default_environment' => 'typrint',
                'typrint' => [
                    'name' => DB_NAME,
                    'connection' => $connection->instance(),
                ],
            ],
            'feature_flags' => [
                'column_null_default' => false,
                'add_timestamps_use_datetime' => true,
            ],
        ]);
        $manager = new Manager($config, new StringInput(''), new NullOutput());
        $manager->migrate('typrint');

        $connection->destruct();
    }
}
