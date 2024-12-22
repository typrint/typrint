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

/**
 * Trait QueryBuilder.
 */
trait QueryBuilder
{
    protected string $table = '';

    protected array $select = [];

    protected array $join = [];

    protected array $where = [];

    protected array $order = [];

    protected array $group = [];

    protected array $having = [];

    protected int $offset = 0;

    protected int $limit = 0;

    protected string $lock = '';

    public function table(string $table): ConnectionInterface
    {
        $this->table = $table;

        return $this;
    }

    public function select(string ...$fields): ConnectionInterface
    {
        $this->select = array_merge($this->select, $fields);

        return $this;
    }

    public function join(string $table, string $on, ...$values): ConnectionInterface
    {
        $this->join[] = ['INNER JOIN', $table, $on, $values];

        return $this;
    }

    public function leftJoin(string $table, string $on, ...$values): ConnectionInterface
    {
        $this->join[] = ['LEFT JOIN', $table, $on, $values];

        return $this;
    }

    public function rightJoin(string $table, string $on, ...$values): ConnectionInterface
    {
        $this->join[] = ['RIGHT JOIN', $table, $on, $values];

        return $this;
    }

    public function fullJoin(string $table, string $on, ...$values): ConnectionInterface
    {
        $this->join[] = ['FULL JOIN', $table, $on, $values];

        return $this;
    }

    public function where(string $expr, ...$values): ConnectionInterface
    {
        $this->where[] = ['AND', $expr, $values];

        return $this;
    }

    public function or(string $expr, ...$values): ConnectionInterface
    {
        $this->where[] = ['OR', $expr, $values];

        return $this;
    }

    public function order(string $field, string $order): ConnectionInterface
    {
        if (!in_array($order, ['asc', 'desc'])) {
            throw new \RuntimeException('Sort can only be asc or desc.');
        }
        $this->order[] = [$field, strtoupper($order)];

        return $this;
    }

    public function group(string ...$fields): ConnectionInterface
    {
        $this->group = array_merge($this->group, $fields);

        return $this;
    }

    public function having(string $expr, ...$values): ConnectionInterface
    {
        $this->having[] = [$expr, $values];

        return $this;
    }

    /**
     * offset.
     */
    public function offset(int $length): ConnectionInterface
    {
        $this->offset = $length;

        return $this;
    }

    /**
     * limit.
     */
    public function limit(int $length): ConnectionInterface
    {
        $this->limit = $length;

        return $this;
    }

    /**
     * 意向排它锁
     */
    public function lockForUpdate(): ConnectionInterface
    {
        $this->lock = 'FOR UPDATE';

        return $this;
    }

    /**
     * 意向共享锁
     */
    public function sharedLock(): ConnectionInterface
    {
        $this->lock = 'LOCK IN SHARE MODE';

        return $this;
    }

    protected function build(string $index, array $data = []): array
    {
        $sql = $values = [];

        // select
        if ('SELECT' == $index) {
            if ($this->select) {
                $select = implode(', ', $this->select);
                $sql[] = "SELECT {$select}";
            } else {
                $sql[] = 'SELECT *';
            }
        }

        // delete
        if ('DELETE' == $index) {
            $sql[] = 'DELETE';
        }

        // table
        if ($this->table) {
            // update
            if ('UPDATE' == $index) {
                $set = [];
                foreach ($data as $k => $v) {
                    $set[] = "{$k} = ?";
                    $values[] = $v;
                }
                $sql[] = "UPDATE {$this->table} SET ".implode(', ', $set);
            } else {
                $sql[] = "FROM {$this->table}";
            }
        }

        // join
        if ($this->join) {
            foreach ($this->join as $item) {
                [$keyword, $table, $on, $vals] = $item;
                $sql[] = "{$keyword} {$table} ON {$on}";
                array_push($values, ...$vals);
            }
        }

        // where
        if ($this->where) {
            $sql[] = 'WHERE';
            foreach ($this->where as $key => $item) {
                [$keyword, $expr, $vals] = $item;

                // in
                foreach ($vals as $k => $val) {
                    if (is_array($val)) {
                        foreach ($val as &$value) {
                            if (is_string($value)) {
                                $value = "'{$value}'";
                            }
                        }
                        unset($value);
                        $expr = preg_replace('/\(\?\)/', sprintf('(%s)', implode(',', $val)), $expr, 1);
                        unset($vals[$k]);
                    }
                }

                if (0 == $key) {
                    $sql[] = "{$expr}";
                } else {
                    $sql[] = "{$keyword} {$expr}";
                }
                array_push($values, ...$vals);
            }
        }

        // group
        if ($this->group) {
            $sql[] = 'GROUP BY '.implode(', ', $this->group);
        }

        // having
        if ($this->having) {
            $subSql = [];
            foreach ($this->having as $item) {
                [$expr, $vals] = $item;
                $subSql[] = "{$expr}";
                array_push($values, ...$vals);
            }
            $subSql = 1 == count($subSql) ? array_pop($subSql) : implode(' AND ', $subSql);
            $sql[] = "HAVING {$subSql}";
        }

        // order
        if ($this->order) {
            $subSql = [];
            foreach ($this->order as $item) {
                [$field, $order] = $item;
                $subSql[] = "{$field} {$order}";
            }
            $sql[] = 'ORDER BY '.implode(', ', $subSql);
        }

        // limit and offset
        if ($this->limit > 0) {
            $sql[] = 'LIMIT ?, ?';
            array_push($values, $this->offset, $this->limit);
        }

        // lock
        if ($this->lock) {
            $sql[] = $this->lock;
        }

        // clear
        $this->table = '';
        $this->select = [];
        $this->join = [];
        $this->where = [];
        $this->order = [];
        $this->group = [];
        $this->having = [];
        $this->offset = 0;
        $this->limit = 0;
        $this->lock = '';

        // 聚合
        return [implode(' ', $sql), $values];
    }
}
