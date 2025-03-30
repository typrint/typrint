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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swow\Coroutine;
use Swow\CoroutineException;
use Swow\Errno;
use Swow\Http\Protocol\ProtocolException as HttpProtocolException;
use Swow\Psr7\Server\Server as SwowServer;
use Swow\SocketException;

class Server
{
    private SwowServer $server;
    private string $address;
    private int $port;
    private \Closure $handler;

    public function __construct(string $address, int $port, $handler)
    {
        $this->server = new SwowServer();
        $this->address = $address;
        $this->port = $port;
        $this->handler = $handler;
    }

    public function start(): void
    {
        $this->server->bind($this->address, $this->port)->listen();
        $handler = $this->handler;
        while (true) {
            try {
                $connection = $this->server->acceptConnection();
                Coroutine::run(static function () use ($connection, $handler): void {
                    try {
                        while (true) {
                            $request = null;
                            try {
                                /** @var ServerRequestInterface $request */
                                $request = $connection->recvHttpRequest();
                                $response = $handler($request);
                                if (null !== $response) {
                                    if ($response instanceof ResponseInterface) {
                                        $connection->sendHttpResponse($response);
                                    } elseif (is_array($response)) {
                                        $connection->respond(...$response);
                                    } else {
                                        $connection->respond($response);
                                    }
                                }
                            } catch (HttpProtocolException $exception) {
                                $connection->error($exception->getCode(), $exception->getMessage(), close: true);
                                break;
                            }
                            if (!$connection->shouldKeepAlive()) {
                                break;
                            }
                        }
                    } catch (\Exception) {
                    } finally {
                        $connection->close();
                    }
                });
            } catch (CoroutineException|SocketException $exception) {
                if (in_array($exception->getCode(), [Errno::EMFILE, Errno::ENFILE, Errno::ENOMEM], true)) {
                    sleep(1);
                } else {
                    break;
                }
            }
        }
    }

    public function stop(): void
    {
        $this->server->close();
    }
}
