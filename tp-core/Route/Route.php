<?php

declare(strict_types=1);

namespace TP\Route;

use Illuminate\Contracts\Container\BindingResolutionException;
use Swow\Psr7\Message\ServerRequest;
use Swow\Psr7\Server\Server;
use Swow\Psr7\Server\EventDriver;
use Swow\Psr7\Server\ServerConnection;

use TP\Container\Container;

class Route
{
    private Container $container;

    public function __construct()
    {
        $this->container = Container::getInstance();
        $this->container->singleton('server', fn() => new EventDriver());
    }

    public function listen(): callable
    {
        return fn() => $this->start();
    }

    public function start(): void
    {
        try {
            /** @var EventDriver $server */
            $server = $this->container->make('server');
        } catch (BindingResolutionException $e) {
            echo $e->getMessage();
            exit(1);
        }

        $server = $server->withStartHandler(static function (Server $server): void {
            echo sprintf(
                "[%s] Server started at %s:%s\n",
                date('Y-m-d H:i:s'),
                $server->getSockAddress(),
                $server->getSockPort()
            );
        });
        $server = $server->withRequestHandler(
            static function (ServerConnection $connection, ServerRequest $request): string {
                echo sprintf(
                    "%s on %s\n",
                    $request->getMethod(),
                    $request->getUri()->getPath()
                );
                return 'Hello Swow!';
            }
        );

        $server->startOn(SERVER_ADDRESS, SERVER_PORT);
    }

    public function reload(): void
    {
    }
}