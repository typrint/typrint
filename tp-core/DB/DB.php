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

namespace TP\DB;

use TP\DB\Pool\ConnectionPool;
use TP\DB\Pool\ConnectionPoolOptions;
use TP\DB\Pool\WaitTimeoutException;
use TP\Utils\Once;

class DB
{
    use ConnectionPoolOptions;

    private static DB $instance;

    private static Once $once;

    protected string $dsn = '';

    /**
     * Database options.
     */
    protected array $options = [];

    /**
     * Connection pool.
     */
    protected ?ConnectionPool $pool;

    protected ?LoggerInterface $logger;

    public static function init(): void
    {
        self::$once = new Once();
    }

    public static function instance(): self
    {
        if (!isset(self::$instance)) {
            self::$once->do(fn () => self::$instance = new self());
        }

        return self::$instance;
    }

    /**
     * Database constructor.
     */
    public function __construct(array $options = [])
    {
        match (DB_TYPE) {
            'mysql' => $this->dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                DB_HOST,
                DB_PORT,
                DB_NAME
            ),
            'pgsql', 'postgres', 'postgresql' => $this->dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME
            ),
            'sqlite' => $this->dsn = sprintf(
                'sqlite:%s',
                DB_NAME
            ),
            default => $this->dsn = ''
        };
        $this->options = $options;
        $this->logger = null;
        $this->createPool();
    }

    protected function createPool(): void
    {
        $dialer = fn () => new Driver(
            $this->dsn,
            DB_USER,
            DB_PASSWORD,
            $this->options
        );
        $this->pool = new ConnectionPool(
            $dialer->bindTo($this),
            $this->maxOpen,
            $this->maxIdle,
            $this->maxLifetime,
            $this->waitTimeout
        );
    }

    public function startPool(int $maxOpen, int $maxIdle, int $maxLifetime = 0, float $waitTimeout = 0.0): void
    {
        $this->maxOpen = $maxOpen;
        $this->maxIdle = $maxIdle;
        $this->maxLifetime = $maxLifetime;
        $this->waitTimeout = $waitTimeout;
        $this->createPool();
    }

    public function setMaxOpen(int $maxOpen): void
    {
        $this->maxOpen = $maxOpen;
    }

    public function setMaxIdle(int $maxIdle): void
    {
        $this->maxIdle = $maxIdle;
    }

    public function setMaxLifetime(int $maxLifetime): void
    {
        $this->maxLifetime = $maxLifetime;
    }

    public function setWaitTimeout(float $waitTimeout): void
    {
        $this->waitTimeout = $waitTimeout;
    }

    public function poolStats(): array
    {
        return $this->pool->stats();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Borrow a connection.
     *
     * @throws WaitTimeoutException
     */
    public function connection(): Connection
    {
        $driver = $this->pool->borrow();

        return new Connection($driver, $this->logger);
    }

    public function debug(\Closure $func): ConnectionInterface
    {
        return $this->connection()->debug($func);
    }

    /**
     * Run query.
     *
     * @param array $values
     *
     * @throws \Throwable
     */
    public function raw(string $sql, ...$values): ConnectionInterface
    {
        return $this->connection()->raw($sql, ...$values);
    }

    /**
     * Execute query.
     *
     * @param array $values
     *
     * @throws \Throwable
     */
    public function exec(string $sql, ...$values): ConnectionInterface
    {
        return $this->connection()->exec($sql, ...$values);
    }

    /**
     * Insert.
     *
     * @throws \Throwable
     */
    public function insert(string $table, array $data, string $insert = 'INSERT INTO'): ConnectionInterface
    {
        return $this->connection()->insert($table, $data, $insert);
    }

    /**
     * Batch insert.
     *
     * @throws \Throwable
     */
    public function batchInsert(string $table, array $data, string $insert = 'INSERT INTO'): ConnectionInterface
    {
        return $this->connection()->batchInsert($table, $data, $insert);
    }

    /**
     * Automatically transaction.
     *
     * @throws \Throwable
     */
    public function transaction(\Closure $closure): void
    {
        $this->connection()->transaction($closure);
    }

    /**
     * Start a new transaction.
     */
    public function beginTransaction(): Transaction
    {
        return $this->connection()->beginTransaction();
    }

    /**
     * Start a new query builder.
     */
    public function table(string $table): ConnectionInterface
    {
        return $this->connection()->table($table);
    }
}
