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
 * A context that wraps a parent context and adds a value.
 */
class ValueContext implements ContextInterface
{
    private ContextInterface $parent;
    private string $key;
    private mixed $val;

    public function __construct(ContextInterface $parent, string $key, mixed $val)
    {
        $this->parent = $parent;
        $this->key = $key;
        $this->val = $val;
    }

    public function done(): ?Channel
    {
        return $this->parent->done();
    }

    public function deadline(): ?\DateTimeInterface
    {
        return $this->parent->deadline();
    }

    public function err(): ?string
    {
        return $this->parent->err();
    }

    public function value(string $key): mixed
    {
        if ($key === $this->key) {
            return $this->val;
        }

        return $this->parent->value($key);
    }
}
