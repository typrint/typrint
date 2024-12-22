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

use Swow\Channel;
use Swow\Exception;
use TP\DB\Driver;

/**
 * ConnectionPool is a pool of db connections.
 */
class ConnectionPool
{
    use ConnectionPoolOptions;

    /**
     * Dialer.
     */
    protected \Closure $dialer;

    /**
     * Connection queue.
     */
    protected Channel $queue;

    /**
     * Active connections.
     */
    protected array $actives = [];

    /**
     * ConnectionPool constructor.
     */
    public function __construct(
        \Closure $dialer,
        int $maxOpen = -1,
        int $maxIdle = -1,
        int $maxLifetime = 0,
        float $waitTimeout = 0.0
    ) {
        $this->dialer = $dialer;
        $this->maxOpen = $maxOpen;
        $this->maxIdle = $maxIdle;
        $this->maxLifetime = $maxLifetime;
        $this->waitTimeout = $waitTimeout;
        // Set the default value of maxOpen and maxIdle to the number of CPU cores
        // TODO Swow currently does not support getting the number of CPU cores
        $cpuCores = $this->cpuNum();
        if (-1 == $maxOpen) {
            $this->maxOpen = $cpuCores;
        }
        if (-1 == $maxIdle) {
            $this->maxIdle = $cpuCores;
        }

        // Initialize the connection queue
        $this->queue = new Channel($this->maxIdle);
    }

    /**
     * Create a new connection.
     */
    protected function createConnection(): object
    {
        // Connection creation will suspend the current coroutine, causing actives to not increase, so set actives first and unset it after creation
        $closure = function () {
            /** @var Driver $connection */
            $connection = ($this->dialer)();
            $connection->pool = $this;
            $connection->createTime = time();

            return $connection;
        };
        $id = spl_object_hash($closure);
        $this->actives[$id] = '';
        try {
            $connection = $closure();
        } finally {
            unset($this->actives[$id]);
        }

        return $connection;
    }

    /**
     * Borrow a connection.
     *
     * @throws WaitTimeoutException
     */
    public function borrow(): object
    {
        /* @var object $connection */
        if ($this->getIdleNumber() > 0 || ($this->maxOpen && $this->getTotalNumber() >= $this->maxOpen)) {
            // If the queue has a connection, take it from the queue
            $connection = $this->pop();
        } else {
            // Create a new connection
            $connection = $this->createConnection();
        }

        // Register the connection
        $id = spl_object_hash($connection);
        $this->actives[$id] = '';

        // Check if the connection has expired
        if ($this->maxLifetime && $connection->createTime + $this->maxLifetime <= time()) {
            $this->discard($connection);

            return $this->borrow();
        }

        return $connection;
    }

    /**
     * Return the connection.
     */
    public function return(object $connection): void
    {
        $id = spl_object_hash($connection);
        if (!isset($this->actives[$id])) {
            return;
        }
        unset($this->actives[$id]);
        $this->push($connection);
    }

    /**
     * Discard the connection.
     */
    public function discard(object $connection): void
    {
        $id = spl_object_hash($connection);
        if (!isset($this->actives[$id])) {
            return;
        }
        unset($this->actives[$id]);
        $this->push($this->createConnection());
    }

    /**
     * Get the connection pool statistics.
     */
    public function stats(): array
    {
        return [
            'total' => $this->getTotalNumber(),
            'idle' => $this->getIdleNumber(),
            'active' => $this->getActiveNumber(),
        ];
    }

    /**
     * Push the connection into the queue.
     */
    protected function push(object $connection): void
    {
        $this->queue->push($connection);
    }

    /**
     * Pop the connection from the queue.
     *
     * @throws WaitTimeoutException
     * @throws Exception
     */
    protected function pop(): object
    {
        $timeout = -1;
        if ($this->waitTimeout) {
            $timeout = $this->waitTimeout;
        }
        $object = $this->queue->pop($timeout);
        if (!$object) {
            if (-1 != $timeout) {
                throw new WaitTimeoutException(sprintf('Wait timeout: %fs', $timeout));
            }
            throw new Exception('Channel a deadlock');
        }

        return $object;
    }

    /**
     * Get the number of idle connections.
     */
    protected function getIdleNumber(): int
    {
        return $this->queue->getLength();
    }

    /**
     * Get the number of active connections.
     */
    protected function getActiveNumber(): int
    {
        return count($this->actives);
    }

    /**
     * Get the total number of connections.
     */
    protected function getTotalNumber(): int
    {
        return $this->getIdleNumber() + $this->getActiveNumber();
    }

    /**
     * Get the number of CPU cores.
     */
    private function cpuNum(): int
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return 1;
        }
        $count = 4;
        if (is_callable('shell_exec')) {
            if ('darwin' === strtolower(PHP_OS)) {
                $count = (int) shell_exec('sysctl -n machdep.cpu.core_count');
            } else {
                $count = (int) shell_exec('nproc');
            }
        }

        return $count > 0 ? $count : 4;
    }
}
