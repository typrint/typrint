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

use Swow\Sync\WaitGroup as SwowWaitGroup;

/**
 * Class WaitGroup.
 *
 * A simple wrapper around Swow's WaitGroup to provide a more user-friendly API.
 */
class WaitGroup
{
    private SwowWaitGroup $wg;

    public function __construct()
    {
        $this->wg = new SwowWaitGroup();
    }

    /**
     * Add a task to the wait group.
     *
     * @param int $count the number of tasks to add
     */
    public function add(int $count = 1): void
    {
        $this->wg->add($count);
    }

    /**
     * Wait for all tasks to complete.
     *
     * @param int $timeout the timeout in milliseconds. Default is -1 (no timeout).
     */
    public function wait(int $timeout = -1): void
    {
        $this->wg->wait($timeout);
    }

    /**
     * Done indicates that a task has completed.
     */
    public function done(): void
    {
        $this->wg->done();
    }
}
