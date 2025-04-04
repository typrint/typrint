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

namespace TP;

use TP\Cache\Cache;
use TP\Cli\Color;
use TP\DB\DB;
use TP\DB\Migrator\Migrator;
use TP\Hook\Hook;
use TP\Loader\PluginLoader;
use TP\Route\Route;
use TP\Utils\Async;
use TP\Utils\Signal;
use TP\Utils\WaitGroup;

use function Swow\defer;

class TP
{
    public function __construct()
    {
        // Load TyPrint configuration
        require_once ABSPATH.'/tp-config.php';
    }

    public function run(): void
    {
        $wg = new WaitGroup();

        Color::printf(Color::GREEN, "Starting TyPrint...\n");

        Hook::init();
        DB::init();
        Cache::init();
        Route::init();

        Migrator::run();

        Hook::instance()->doAction('before_load_plugins');
        PluginLoader::instance();
        Hook::instance()->doAction('after_load_plugins');

        $wg->add(1);
        Async::run(static function () use ($wg): void {
            defer(fn () => $wg->done());
            Route::instance()->listen();
        });

        Color::printf(Color::GREEN, "TyPrint is ready!\n");
        Hook::instance()->doAction('after_start');

        // Handle signals for graceful shutdown
        Async::run(static function (): void {
            Signal::wait(Signal::INT);
            Color::printf(Color::GREEN, "Stopping TyPrint...\n");
            Route::instance()->shutdown();
        });
        Async::run(static function (): void {
            Signal::wait(Signal::TERM);
            Color::printf(Color::GREEN, "Stopping TyPrint...\n");
            Route::instance()->shutdown();
        });

        $wg->wait();
        Hook::instance()->doAction('before_shutdown');
    }
}
