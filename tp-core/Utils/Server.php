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

namespace TP\Utils;

use Psr\Http\Message\ServerRequestInterface;
use Swow\Psr7\Server\EventDriver;
use Swow\Psr7\Server\ServerConnection;

class Server
{
    private EventDriver $server;
    private string $address;
    private int $port;
    private \Closure $handler;

    public function __construct(string $address, int $port, $handler)
    {
        $this->server = new EventDriver();
        $this->address = $address;
        $this->port = $port;
        $this->handler = $handler;
    }

    public function start(): void
    {
        $handler = $this->handler;
        $this->server->withRequestHandler(
            static function (ServerConnection $connection, ServerRequestInterface $request) use ($handler): string {
                return $handler($request);
            }
        );
        $this->server->startOn($this->address, $this->port);
    }
}
