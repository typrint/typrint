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

/**
 * Class Driver.
 */
class Driver
{
    protected string $dsn = '';

    protected string $username = 'root';

    protected string $password = '';

    /**
     * Default connection options.
     */
    protected array $options = [];

    /**
     * PDO object.
     */
    protected ?\PDO $pdo;

    /**
     * Connection pool object.
     */
    public ?ConnectionPool $pool;

    /**
     * Time of creation, used to determine the age of the connection.
     */
    public int $createTime;

    protected array $defaultOptions = [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_TIMEOUT => 5,
    ];

    /**
     * Driver constructor.
     *
     * @throws \PDOException
     */
    public function __construct(string $dsn, string $username, string $password, array $options = [])
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
        $this->connect();
    }

    /**
     * Get instance.
     */
    public function instance(): \PDO
    {
        return $this->pdo;
    }

    /**
     * Get options.
     */
    public function options(): array
    {
        return $this->options + $this->defaultOptions;
    }

    /**
     * Connect to database.
     *
     * @throws \PDOException
     */
    public function connect(): void
    {
        $this->pdo = new \PDO(
            $this->dsn,
            $this->username,
            $this->password,
            $this->options()
        );
    }

    /**
     * Close connection.
     */
    public function close(): void
    {
        $this->pdo = null;
    }

    public function __discard(): void
    {
        $this->pool?->discard($this);
    }

    public function __return(): void
    {
        $this->pool?->return($this);
    }
}
