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
use TP\Admin\Route as AdminRoute;
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
    }

    public function listen(): callable
    {
        $this->server->setHandler(fn (ServerRequestInterface $request): ResponseInterface => $this->router->handle($request));

        return fn () => $this->server->start();
    }

    public function shutdown(): void
    {
        $this->server->stop();
    }
}
