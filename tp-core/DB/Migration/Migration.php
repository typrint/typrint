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

namespace TP\DB\Migration;

use Phinx\Config\Config;
use Phinx\Migration\Manager;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use TP\DB\DB;

class Migration
{
    public static function run(): void
    {
        $config = new Config([
            'paths' => [
                'migrations' => ABSPATH.'/tp-core/DB/Migration/migrations',
                'seeds' => ABSPATH.'/tp-core/DB/Migration/seeds',
            ],
            'environments' => [
                'default_migration_table' => DB_TABLE_PREFIX.'migrations',
                'default_environment' => 'typrint',
                'typrint' => [
                    'connection' => DB::instance()->connection()->instance(),
                ],
            ],
        ]);
        $manager = new Manager($config, new StringInput(''), new NullOutput());
        $manager->migrate('typrint');
    }
}
