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

use Psr\Http\Message\ServerRequestInterface;
use TP\Utils\Once;
use TP\Utils\Server;

class Route
{
    private static Route $instance;

    private static Once $once;
    private Server $server;

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
        $this->server = new Server(SERVER_ADDRESS, SERVER_PORT, static function (ServerRequestInterface $request): string {
            echo sprintf(
                "%s on %s\n",
                $request->getMethod(),
                $request->getUri()->getPath()
            );

            return 'Hello Swow!';
        });
    }

    public function listen(): callable
    {
        return fn () => $this->server->start();
    }

    public function shutdown(): void
    {
        $this->server->stop();
    }
}
