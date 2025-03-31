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

class Endpoint
{
    public ?\Closure $handler = null;
    public string $pattern = '';
    public array $paramKeys = [];

    public function __construct($handler = null, string $pattern = '', array $paramKeys = [])
    {
        $this->handler = $handler;
        $this->pattern = $pattern;
        $this->paramKeys = $paramKeys;
    }
}
