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

class Once
{
    protected Channel $chan;

    protected bool $executed = false;

    public function __construct()
    {
        $this->chan = new Channel(1);
        $this->chan->push(true);
    }

    public function do(\Closure $func): void
    {
        $result = $this->chan->pop();
        if ($result) {
            try {
                $this->executed = true;
                $func();
            } finally {
                $this->chan->close();
            }
        }
    }
}
