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

namespace TP\Utils\Chi;

class Context
{
    public string $routePattern = '';
    public array $routePatterns = [];
    public RouteParams $routeParams;
    public array $methodsAllowed = [];
    public bool $methodNotAllowed = false;

    public function __construct()
    {
        $this->routeParams = new RouteParams();
    }
}
