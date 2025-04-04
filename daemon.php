#!/usr/bin/env php
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

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use TP\Cli\Color;
use TP\Filesystem\Watcher\Watcher;
use TP\Utils\Async;
use TP\Utils\Signal;

// Loads the TyPrint autoloader.
require_once __DIR__.'/tp-vendor/autoload.php';

$logo = <<<EOT
      ______      ____               __
     /_  __/_  __/ __ \_____(_)___  / /_
      / / / / / / /_/ / ___/ / __ \/ __/
     / / / /_/ / ____/ /  / / / / / /_
    /_/  \___ /_/   /_/  /_/_/ /_/\__/
        /____/
    EOT;

Color::printf(null, $logo."\n");

$isDev = in_array('--dev', $argv);
$running = true;

Async::run(static function () use (&$running): void {
    Signal::wait(Signal::INT);
    Color::printf(Color::YELLOW, "Received SIGINT signal, stopping daemon...\n");
    $running = false;
});
Async::run(static function () use (&$running): void {
    Signal::wait(Signal::TERM);
    Color::printf(Color::YELLOW, "Received SIGTERM signal, stopping daemon...\n");
    $running = false;
});

$process = new Process(
    command: [(new ExecutableFinder())->find('php'), 'index.php'],
    timeout: null
);
$process->setTty(posix_isatty(STDOUT));

if ($isDev) {
    $watcher = new Watcher();
    $watcher->setPaths(__DIR__);

    $watcher->onAnyChange(function ($event, $path) use (&$process) {
        if ($process->isRunning()) {
            if ('php' === pathinfo($path, PATHINFO_EXTENSION)) {
                Color::printf(Color::YELLOW, "Watcher: %s changed, restarting...\n", $path);
                $process->stop(3, SIGTERM);
            }
        }
    });

    Async::run($watcher->startFn());
    Color::printf(Color::YELLOW, "Developer mode enabled, watching for changes...\n");
}

Color::printf(Color::GREEN, "Daemon PID: %d\n", getmypid());

while ($running) {
    $process->start();
    Color::printf(Color::GREEN, "TyPrint PID: %d\n", $process->getPid());
    while ($process->isRunning() && $running) {
        usleep(100000); // sleep for 0.1 seconds
    }
    if (0 !== $process->getExitCode() && $running) {
        Color::printf(Color::RED, "TyPrint exited with code %d\n", $process->getExitCode());
        Color::printf(Color::YELLOW, "Restarting in 1 second...\n");
        sleep(1);
    }
}
