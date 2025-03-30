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

use TP\Utils\Channel;

/**
 * A context that can be canceled.
 */
class CancelContext implements ContextInterface
{
    private ContextInterface $parent;
    private Channel $ch;
    private bool $canceled = false;
    private ?string $err = null;

    public function __construct(ContextInterface $parent)
    {
        $this->parent = $parent;
        $this->ch = new Channel(1); // Buffer size 1 for non-blocking send
    }

    public function done(): ?Channel
    {
        return $this->ch;
    }

    public function deadline(): ?\DateTimeInterface
    {
        return $this->parent->deadline();
    }

    public function err(): ?string
    {
        if (!$this->canceled) {
            return $this->parent->err();
        }

        return $this->err;
    }

    public function value(string $key): mixed
    {
        return $this->parent->value($key);
    }

    /**
     * Cancels this context with optional error message.
     */
    public function cancel(string $err = 'context canceled'): void
    {
        if ($this->canceled) {
            return;
        }

        $this->canceled = true;
        $this->err = $err;
        $this->ch->close();
    }
}
