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
use TP\DB\DB;
use TP\DB\Migration\Migrator;
use TP\Filesystem\Watcher\Watcher;
use TP\Route\Route;
use TP\Utils\Async;
use TP\Utils\Channel;
use TP\Utils\Signal;

class TP
{
    public function __construct()
    {
        // Load TyPrint configuration
        require_once ABSPATH.'/tp-config.php';
    }

    public function run(): void
    {
        $channel = new Channel();

        // Initialize Router
        Route::init();
        Async::run(Route::instance()->listen());

        // Initialize Database
        DB::init();
        Migrator::run();

        // Initialize Cache
        Cache::init();

        // Listen file changes
        $watcher = new Watcher();
        $watcher->setPaths(ABSPATH);
        $watcher->onAnyChange(function ($event, $path) {
            echo "File {$path} has been {$event}\n";
        });
        Async::run($watcher->startFn());

        // Handle signals for graceful shutdown
        Async::run(static function () use ($channel): void {
            Signal::wait(Signal::INT);
            Route::instance()->shutdown();
            $channel->push("Terminated by SIGINT\n");
        });
        Async::run(static function () use ($channel): void {
            Signal::wait(Signal::TERM);
            Route::instance()->shutdown();
            $channel->push("Terminated by SIGTERM\n");
        });
        $channel->pop();
    }
}
