<?php

declare(strict_types=1);

namespace TP;

use Swow\Channel;
use Swow\Coroutine;
use Swow\Signal;

use TP\Container\Container;
use TP\Filesystem\Watcher\Watcher;
use TP\Route\Route;

class TP
{
    public function __construct()
    {
        // Load TyPrint configuration
        require_once ABSPATH . '/tp-config.php';
        $this->define('TP', 'tp-core');
    }

    public function define(string $name, string $value): void
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    public function run(): void
    {
        // Boot TyPrint Container
        $channel = new Channel();
        $container = Container::getInstance();

        // Boot TyPrint Router
        $router = new Route();
        Coroutine::run($router->listen());

        // Listen file changes
        $watcher = new Watcher();
        $watcher->setPaths(ABSPATH);
        $watcher->onAnyChange(function ($event, $path) {
            echo "File $path has been $event\n";
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
