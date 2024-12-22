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

use Swow\Channel;
use Swow\Coroutine;
use Swow\Signal;
use TP\DB\DB;
use TP\Filesystem\Watcher\Watcher;
use TP\Route\Route;

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
        Coroutine::run(Route::instance()->listen());

        // Initialize Database
        DB::init();

        // Listen file changes
        $watcher = new Watcher();
        $watcher->setPaths(ABSPATH);
        $watcher->onAnyChange(function ($event, $path) {
            echo "File {$path} has been {$event}\n";
        });
        Coroutine::run($watcher->startFn());

        // Handle signals for graceful shutdown
        Coroutine::run(static function () use ($channel): void {
            Signal::wait(Signal::INT);
            $channel->push("Terminated by SIGINT\n");
        });
        Coroutine::run(static function () use ($channel): void {
            Signal::wait(Signal::TERM);
            $channel->push("Terminated by SIGTERM\n");
        });
        $channel->pop();
    }
}
