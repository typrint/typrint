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

namespace TP\Route;

use Swow\Psr7\Message\ServerRequest;
use Swow\Psr7\Server\EventDriver;
use Swow\Psr7\Server\Server;
use Swow\Psr7\Server\ServerConnection;
use TP\Once;

class Route
{
    private static Route $instance;

    private static Once $once;
    private EventDriver $server;

    public static function init(): void
    {
        self::$once = new Once();
    }

    public static function instance(): self
    {
        if (!isset(self::$instance)) {
            self::$once->do(fn () => self::$instance = new self());
        }

        return self::$instance;
    }

    public function __construct()
    {
        $this->server = new EventDriver();
    }

    public function listen(): callable
    {
        return fn () => $this->start();
    }

    public function start(): void
    {
        $this->server = $this->server->withStartHandler(static function (Server $server): void {
            echo sprintf(
                "[%s] Server started at %s:%s\n",
                date('Y-m-d H:i:s'),
                $server->getSockAddress(),
                $server->getSockPort()
            );
        });
        $this->server = $this->server->withRequestHandler(
            static function (ServerConnection $connection, ServerRequest $request): string {
                echo sprintf(
                    "%s on %s\n",
                    $request->getMethod(),
                    $request->getUri()->getPath()
                );

                return 'Hello Swow!';
            }
        );

        $this->server->startOn(SERVER_ADDRESS, SERVER_PORT);
    }

    public function reload(): void
    {
    }
}
