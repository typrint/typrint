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

namespace TP\Context;

use Swow\Coroutine;

/**
 * A context that is canceled when a deadline is reached.
 */
class DeadlineContext extends CancelContext
{
    private \DateTimeInterface $deadline;
    private ?int $timerId = null;

    public function __construct(ContextInterface $parent, \DateTimeInterface $deadline)
    {
        parent::__construct($parent);
        $this->deadline = $deadline;

        // Schedule cancellation at deadline
        $now = new \DateTime();
        if ($now >= $deadline) {
            $this->cancel('context deadline exceeded');

            return;
        }

        // Calculate seconds until deadline
        $seconds = $deadline->getTimestamp() - $now->getTimestamp();

        // Schedule a coroutine to cancel the context after the deadline
        $coroutine = Coroutine::run(function () use ($seconds): void {
            sleep($seconds);
            $this->cancel('context deadline exceeded');
        });
        $this->timerId = $coroutine->getId();
    }

    public function deadline(): ?\DateTimeInterface
    {
        return $this->deadline;
    }

    public function cancel(string $err = 'context canceled'): void
    {
        $coroutine = Coroutine::get($this->timerId);
        $coroutine?->kill();
        parent::cancel($err);
    }
}
