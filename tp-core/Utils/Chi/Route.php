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

class Route
{
    /** @var array <string, \Closure> */
    public array $handlers = [];
    public string $pattern = '';

    public function __construct(array $handlers = [], string $pattern = '')
    {
        $this->handlers = $handlers;
        $this->pattern = $pattern;
    }
}
