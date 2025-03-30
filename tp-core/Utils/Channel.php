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

use Swow\Channel as SwowChannel;

class Channel
{
    private ?SwowChannel $channel;
    private bool $closed = false;

    public function __construct(int $capacity = 1)
    {
        $this->channel = new SwowChannel($capacity);
    }

    /**
     * Tries to send a value on the channel.
     * Returns false if the channel is closed or full.
     *
     * @param mixed $value   the value to send
     * @param int   $timeout The timeout in milliseconds. Default is -1 (no timeout).
     */
    public function push(mixed $value, int $timeout = -1): bool
    {
        if ($this->closed) {
            return false;
        }

        try {
            $this->channel->push($value, $timeout);
        } catch (\Throwable) {
            return false;
        }

        return true;
    }

    /**
     * Tries to receive a value from the channel.
     * Returns value if successful, or null if the channel is closed or empty.
     *
     * @param int $timeout The timeout in milliseconds. Default is -1 (no timeout).
     */
    public function pop(int $timeout = -1): mixed
    {
        if ($this->closed) {
            return false;
        }

        return $this->channel->pop($timeout);
    }

    /**
     * Closes the channel.
     */
    public function close(): void
    {
        if (!$this->closed) {
            $this->closed = true;
            $this->channel->close();
        }
    }

    /**
     * Returns whether the channel is closed.
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * Returns the capacity of the channel.
     */
    public function getCapacity(): int
    {
        return $this->channel->getCapacity();
    }

    /**
     * Returns the number of items in the channel.
     */
    public function getLength(): int
    {
        return $this->channel->getLength();
    }

    /**
     * Returns whether the channel is empty.
     */
    public function isEmpty(): bool
    {
        return $this->channel->isEmpty();
    }

    /**
     * Returns whether the channel is full.
     */
    public function isFull(): bool
    {
        return $this->channel->isFull();
    }
}
