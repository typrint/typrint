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
use TP\Filesystem\Watcher\Watcher;
use TP\Hook\Hook;
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
        $this->beforeRun();

        Hook::init();
        DB::init();
        Cache::init();
        Route::init();

        Migrator::run();

        $wg->add(1);
        Async::run(static function () use ($wg): void {
            defer(fn () => $wg->done());
            Route::instance()->listen();
        });

        // Listen file changes
        $watcher = new Watcher();
        $watcher->setPaths(ABSPATH);
        $watcher->onAnyChange(function ($event, $path) {
            echo "File {$path} has been {$event}\n";
        });
        Async::run($watcher->startFn());

        Color::printf(Color::GREEN, "TyPrint is ready!\n");

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

        Color::printf(Color::GREEN, "TyPrint stopped, bye!\n");
    }

    private function beforeRun(): void
    {
        $logo = <<<EOT
              ______      ____               __
             /_  __/_  __/ __ \_____(_)___  / /_
              / / / / / / /_/ / ___/ / __ \/ __/
             / / / /_/ / ____/ /  / / / / / /_
            /_/  \___ /_/   /_/  /_/_/ /_/\__/
                /____/
            EOT;

        Color::printf(null, $logo."\n");
        Color::printf(Color::GREEN, "Starting TyPrint...\n");
    }
}
