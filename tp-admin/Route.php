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

namespace TP\Admin;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TP\Utils\Chi\Utils;

class Route
{
    private static array $routes = [];

    public static function handle(ServerRequestInterface $request): ResponseInterface
    {
        return Utils::response(200, [], 'ok');
    }
}
