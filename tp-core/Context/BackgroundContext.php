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
 * An empty context that is never canceled.
 */
class BackgroundContext implements ContextInterface
{
    public function done(): ?Channel
    {
        return null; // Never canceled
    }

    public function deadline(): ?\DateTimeInterface
    {
        return null; // No deadline
    }

    public function err(): ?string
    {
        return null; // No error
    }

    public function value(string $key): mixed
    {
        return null; // No values
    }
}
