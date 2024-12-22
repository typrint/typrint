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

class Connection implements ConnectionInterface
{
    use QueryBuilder;

    protected ?Driver $driver;

    protected ?LoggerInterface $logger;

    protected ?\Closure $debug;

    /**
     * PDOStatement.
     */
    protected ?\PDOStatement $statement;

    /**
     * sql.
     */
    protected string $sql = '';

    /**
     * params.
     */
    protected array $params = [];

    /**
     * values.
     */
    protected array $values = [];

    /**
     * @var array [$sql,,,]
     */
    protected array $sqlData = [];

    protected array $options = [];

    /**
     * Cached lastInsertId.
     */
    protected ?string $lastInsertId;

    /**
     * Cached rowCount.
     */
    protected ?int $rowCount;

    /**
     * Because the Driver will be recycled after each execution in coroutine, Connection cannot be reused, and must be
     * borrowed from Database->borrow() each time.
     * Transactions can be executed multiple before commit or rollback.
     */
    protected bool $executed = false;

    public function __construct(Driver $driver, ?LoggerInterface $logger)
    {
        $this->driver = $driver;
        $this->logger = $logger;
        $this->options = $driver->options();
        $this->debug = null;
    }

    /**
     * Connect to database.
     *
     * @throws \PDOException
     */
    public function connect(): void
    {
        $this->driver->connect();
    }

    /**
     * Close the connection.
     */
    public function close(): void
    {
        $this->statement = null;
        $this->driver->close();
    }

    /**
     * 重新连接.
     *
     * @throws \PDOException
     */
    protected function reconnect(): void
    {
        $this->close();
        $this->connect();
    }

    /**
     * @throws \Throwable
     */
    public function raw(string $sql, ...$values): ConnectionInterface
    {
        $this->sql = $sql;
        $this->values = $values;
        $this->sqlData = [$this->sql, $this->params, $this->values, 0];

        return $this->execute();
    }

    /**
     * @throws \Throwable
     */
    public function exec(string $sql, ...$values): ConnectionInterface
    {
        return $this->raw($sql, ...$values);
    }

    /**
     * @throws \Throwable
     */
    public function execute(): ConnectionInterface
    {
        if ($this->executed) {
            throw new \RuntimeException('The Connection::class cannot be executed repeatedly, please use the DB::class call');
        }

        $beginTime = microtime(true);
        $ex = null;
        try {
            $this->prepare();
            $success = $this->statement->execute();
            if (!$success) {
                [$flag, $code, $message] = $this->statement->errorInfo();
                throw new \PDOException(sprintf('%s %d %s', $flag, $code, $message), $code);
            }
        } catch (\Throwable $e) {
            $ex = $e;
            throw $e;
        } finally {
            // Mark as executed
            if (!$this instanceof Transaction) {
                $this->executed = true;
            }

            // Record execution time
            $time = round((microtime(true) - $beginTime) * 1000, 2);
            $this->sqlData[3] = $time;

            // Cache common data so that resources can be recycled in advance
            // Contains pool and executes caching only in non-transactional situations, and returns the connection to
            // the pool in advance to improve concurrency performance and reduce the probability of deadlock
            isset($this->lastInsertId) && $this->lastInsertId = null;
            isset($this->rowCount) && $this->rowCount = null;
            if (isset($ex)) {
                // 有异常: 使用默认值, 不调用 driver, statement
                $this->lastInsertId = '';
                $this->rowCount = 0;
            } elseif ($this->driver->pool && !$this instanceof Transaction) {
                // 有pool: 提前缓存 lastInsertId, rowCount 让连接提前归还
                try {
                    if (str_contains($this->sql, 'INSERT INTO')) {
                        $this->lastInsertId = $this->driver->instance()->lastInsertId();
                    } else {
                        $this->lastInsertId = '';
                    }
                } catch (\Throwable $ex) {
                    $this->lastInsertId = '';
                }
                $this->rowCount = $this->statement->rowCount();
            }

            // logger
            if ($this->logger) {
                $log = $this->queryLog();
                $this->logger->trace(
                    $log['time'],
                    $log['sql'],
                    $log['bindings'],
                    $this->rowCount(),
                    $ex ?? null
                );
            }

            // debug
            $debug = $this->debug;
            $debug && $debug->call($this, $this);
        }

        // Clear data after execution for reuse
        // Do not clear when an exception is thrown, because it needs to be retried after reconnection
        $this->clear();

        // Recycle immediately after execution
        // Do not recycle when an exception is thrown, because it needs to be verified whether it is in a transaction
        // after reconnection
        if ($this->driver->pool && !$this instanceof Transaction) {
            $this->driver->__return();
            $this->driver = null;
        }

        return $this;
    }

    protected function clear(): void
    {
        $this->debug = null;
        $this->sql = '';
        $this->params = [];
        $this->values = [];
    }

    protected function prepare(): void
    {
        if (!empty($this->params)) {
            // Bind params
            $statement = $this->driver->instance()->prepare($this->sql);
            if (!$statement) {
                throw new \PDOException('PDO prepare failed');
            }
            $this->statement = $statement;
            $this->sqlData = [$this->sql, $this->params, [], 0]; // Must before bindParam to avoid type conversion
            foreach ($this->params as $key => &$value) {
                if (!$this->statement->bindParam($key, $value, static::bindType($value))) {
                    throw new \PDOException('PDOStatement bindParam failed');
                }
            }
        } elseif (!empty($this->values)) {
            // Bind values
            $statement = $this->driver->instance()->prepare($this->sql);
            if (!$statement) {
                throw new \PDOException('PDO prepare failed');
            }
            $this->statement = $statement;
            $this->sqlData = [$this->sql, [], $this->values, 0];
            foreach ($this->values as $key => $value) {
                if (!$this->statement->bindValue($key + 1, $value, static::bindType($value))) {
                    throw new \PDOException('PDOStatement bindValue failed');
                }
            }
        } else {
            // No params or values
            $statement = $this->driver->instance()->prepare($this->sql);
            if (!$statement) {
                throw new \PDOException('PDO prepare failed');
            }
            $this->statement = $statement;
            $this->sqlData = [$this->sql, [], [], 0];
        }
    }

    protected static function bindType($value): int
    {
        return match (\gettype($value)) {
            'boolean' => \PDO::PARAM_BOOL,
            'NULL' => \PDO::PARAM_NULL,
            'integer' => \PDO::PARAM_INT,
            default => \PDO::PARAM_STR,
        };
    }

    /**
     * Debug.
     */
    public function debug(\Closure $func): ConnectionInterface
    {
        $this->debug = $func;

        return $this;
    }

    /**
     * Get all rows.
     *
     * @throws \Throwable
     */
    public function get(): array
    {
        if ($this->table) {
            [$sql, $values] = $this->build('SELECT');
            $this->raw($sql, ...$values);
        }

        return $this->queryAll();
    }

    /**
     * Get the first row.
     *
     * @return array|object|false
     *
     * @throws \Throwable
     */
    public function first(): mixed
    {
        if ($this->table) {
            [$sql, $values] = $this->build('SELECT');
            $this->raw($sql, ...$values);
        }

        return $this->queryOne();
    }

    /**
     * Get a single value.
     *
     * @throws \PDOException|\Throwable
     */
    public function value(string $field): mixed
    {
        if ($this->table) {
            [$sql, $values] = $this->build('SELECT');
            $this->raw($sql, ...$values);
        }
        $result = $this->queryOne();
        if (empty($result)) {
            throw new \PDOException(sprintf('Field %s not found', $field));
        }
        $isArray = is_array($result);
        if (($isArray && !isset($result[$field])) || (!$isArray && !isset($result->$field))) {
            throw new \PDOException(sprintf('Field %s not found', $field));
        }

        return $isArray ? $result[$field] : $result->$field;
    }

    /**
     * Update multiple data.
     *
     * @throws \Throwable
     */
    public function updates(array $data): ConnectionInterface
    {
        [$sql, $values] = $this->build('UPDATE', $data);

        return $this->exec($sql, ...$values);
    }

    /**
     * Update single data.
     *
     * @throws \Throwable
     */
    public function update(string $field, $value): ConnectionInterface
    {
        [$sql, $values] = $this->build('UPDATE', [
            $field => $value,
        ]);

        return $this->exec($sql, ...$values);
    }

    /**
     * Delete data.
     *
     * @throws \Throwable
     */
    public function delete(): ConnectionInterface
    {
        [$sql, $values] = $this->build('DELETE');

        return $this->exec($sql, ...$values);
    }

    /**
     * Returns the result set
     * Only available in the debug closure, because the connection is returned to the pool, if there is still a call to
     * the result set, there will be a consistency issue.
     */
    public function statement(): \PDOStatement
    {
        if (!$this->debug) {
            throw new \RuntimeException('The statement method is only available in the debug closure');
        }

        return $this->statement;
    }

    /**
     * Get a single row.
     */
    public function queryOne(int $fetchStyle = \PDO::FETCH_OBJ): mixed
    {
        return $this->statement->fetch($fetchStyle);
    }

    /**
     * Get all rows.
     */
    public function queryAll(int $fetchStyle = \PDO::FETCH_OBJ): array
    {
        return $this->statement->fetchAll($fetchStyle);
    }

    /**
     * Get a single column.
     */
    public function queryColumn(int $columnNumber = 0): array
    {
        $column = [];
        while ($row = $this->statement->fetchColumn($columnNumber)) {
            $column[] = $row;
        }

        return $column;
    }

    /**
     * Get a scalar value.
     */
    public function queryScalar(): mixed
    {
        return $this->statement->fetchColumn();
    }

    /**
     * Get the last insert ID.
     */
    public function lastInsertId(): string
    {
        if (!isset($this->lastInsertId) && $this->driver instanceof Driver) {
            $this->lastInsertId = $this->driver->instance()->lastInsertId();
        }

        return $this->lastInsertId;
    }

    /**
     * Get the number of rows affected by the last SQL statement.
     */
    public function rowCount(): int
    {
        if (!isset($this->rowCount) && $this->driver instanceof Driver) {
            $this->rowCount = $this->statement->rowCount();
        }

        return $this->rowCount;
    }

    /**
     * Get the query log.
     */
    public function queryLog(): array
    {
        $sql = '';
        $params = $values = [];
        $time = 0;
        !empty($this->sqlData) && [$sql, $params, $values, $time] = $this->sqlData;

        return [
            'time' => $time,
            'sql' => $sql,
            'bindings' => $values ?: $params,
        ];
    }

    /**
     * @throws \Throwable
     */
    public function insert(string $table, array $data, string $insert = 'INSERT INTO'): ConnectionInterface
    {
        $keys = array_keys($data);
        $fields = array_map(function ($key) {
            return ":{$key}";
        }, $keys);
        $sql = "{$insert} `{$table}` (`".implode('`, `', $keys).'`) VALUES ('.implode(', ', $fields).')';
        $this->params = array_merge($this->params, $data);

        return $this->exec($sql);
    }

    /**
     * @throws \Throwable
     */
    public function batchInsert(string $table, array $data, string $insert = 'INSERT INTO'): ConnectionInterface
    {
        $keys = array_keys($data[0]);
        $sql = "{$insert} `{$table}` (`".implode('`, `', $keys).'`) VALUES ';
        $values = [];
        $subSql = [];
        foreach ($data as $item) {
            $placeholder = [];
            foreach ($keys as $key) {
                $value = $item[$key];
                $values[] = $value;
                $placeholder[] = '?';
            }
            $subSql[] = '('.implode(', ', $placeholder).')';
        }
        $sql .= implode(', ', $subSql);

        return $this->exec($sql, ...$values);
    }

    /**
     * Check if the current PDO connection is in a transaction
     * If the connection in the transaction is returned to the pool, an error will occur when the transaction is started
     * next time.
     */
    public function inTransaction(): bool
    {
        $pdo = $this->driver->instance();

        return $pdo->inTransaction();
    }

    /**
     * @throws \Throwable
     */
    public function transaction(\Closure $closure): void
    {
        $tx = $this->beginTransaction();
        try {
            $closure($tx);
            $tx->commit();
        } catch (\Throwable $ex) {
            $tx->rollback();
            throw $ex;
        }
    }

    /**
     * @throws \PDOException
     */
    public function beginTransaction(): Transaction
    {
        $driver = $this->driver;
        $this->driver = null; // Make it can't be recycled

        return new Transaction($driver, $this->logger);
    }

    public function __destruct()
    {
        $this->executed = true;

        // Recycle
        if (!$this->driver) {
            return;
        }
        if ($this->inTransaction()) {
            $this->driver->__discard();
            $this->driver = null;

            return;
        }
        $this->driver->__return();
        $this->driver = null;
    }
}
