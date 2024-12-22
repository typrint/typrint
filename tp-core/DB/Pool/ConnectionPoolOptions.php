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

namespace TP\DB\Pool;

trait ConnectionPoolOptions
{
    /**
     * Maximum number of open connections
     * default equals to the number of CPU cores, 0 means unlimited.
     */
    protected int $maxOpen = -1;

    /**
     * Maximum number of idle connections
     * default equals to the number of CPU cores.
     */
    protected int $maxIdle = -1;

    /**
     * Maximum lifetime of a connection
     * 0 means unlimited.
     */
    protected int $maxLifetime = 0;

    /**
     * Maximum wait time when borrowing a connection
     * 0 means unlimited.
     */
    protected float $waitTimeout = 0.0;
}
