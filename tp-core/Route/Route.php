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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swow\Psr7\Server\ServerConnection;
use TP\Admin\Route as AdminRoute;
use TP\Facades\Hook;
use TP\Utils\Chi\Router;
use TP\Utils\Once;
use TP\Utils\Server;

class Route
{
    private static Route $instance;

    private static Once $once;
    private Server $server;
    private Router $router;

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
        $this->router = new Router();
        $this->registerRoutes();
        $this->server = new Server(SERVER_ADDRESS, SERVER_PORT);
    }

    public function registerRoutes(): void
    {
        $this->router->any('/tp-admin', fn (ServerRequestInterface $request): ResponseInterface => AdminRoute::handle($request));
        $this->router->any('/tp-admin/*', fn (ServerRequestInterface $request): ResponseInterface => AdminRoute::handle($request));
        $this->router->any('/*', fn (ServerRequestInterface $request): ResponseInterface => self::handle($request));
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        Hook::doAction('before_request', $request);

        Hook::doAction('after_request', $request);

        Hook::doAction('before_response', $request);
        $response = $this->router->handle($request);
        Hook::doAction('after_response', $response);

        return $response;
    }

    public function listen(): void
    {
        $this->server->setHandler(static function (ServerConnection $connection, ServerRequestInterface $request): ResponseInterface {
            $response = $this->router->handle($request);

            // Set the Connection header before sending the response
            $response = $response->withHeader('Connection', $connection->shouldKeepAlive() ? 'keep-alive' : 'close');

            return $response;
        });

        $this->server->start();
    }

    public function shutdown(): void
    {
        $this->server->stop();
    }
}
