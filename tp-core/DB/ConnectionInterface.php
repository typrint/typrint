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

interface ConnectionInterface
{
    public function instance(): \PDO;

    public function debug(\Closure $func): self;

    public function raw(string $sql, ...$values): self;

    public function exec(string $sql, ...$values): self;

    public function table(string $table): self;

    public function select(string ...$fields): self;

    public function join(string $table, string $on, ...$values): self;

    public function leftJoin(string $table, string $on, ...$values): self;

    public function rightJoin(string $table, string $on, ...$values): self;

    public function fullJoin(string $table, string $on, ...$values): self;

    public function where(string $expr, ...$values): self;

    public function or(string $expr, ...$values): self;

    public function order(string $field, string $order): self;

    public function group(string ...$fields): self;

    public function having(string $expr, ...$values): self;

    public function offset(int $length): self;

    public function limit(int $length): self;

    public function lockForUpdate(): self;

    public function sharedLock(): self;

    public function get(): array;

    public function first(): mixed;

    /**
     * @throws \PDOException
     */
    public function value(string $field): mixed;

    public function updates(array $data): self;

    public function update(string $field, $value): self;

    public function delete(): self;

    /**
     * 自动事务
     *
     * @throws \Throwable
     */
    public function transaction(\Closure $closure);

    public function beginTransaction(): Transaction;

    public function statement(): \PDOStatement;

    public function lastInsertId(): string;

    public function rowCount(): int;

    public function queryLog(): array;
}
