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
 * Context interface modeled after Golang's context.
 */
interface ContextInterface
{
    /**
     * Returns a channel that's closed when this Context is canceled or times out.
     */
    public function done(): ?Channel;

    /**
     * Returns the time when this Context will be canceled, if any.
     */
    public function deadline(): ?\DateTimeInterface;

    /**
     * Returns the error indicating why this context was canceled.
     */
    public function err(): ?string;

    /**
     * Returns the value associated with the key, or null if none.
     */
    public function value(string $key): mixed;
}
