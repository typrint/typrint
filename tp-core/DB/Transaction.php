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

class Transaction extends Connection
{
    /**
     * Transaction constructor.
     */
    public function __construct(Driver $driver, ?LoggerInterface $logger)
    {
        parent::__construct($driver, $logger);
        if (!$this->driver->instance()->beginTransaction()) {
            throw new \PDOException('Begin transaction failed');
        }
    }

    /**
     * 提交事务
     *
     * @throws \PDOException
     */
    public function commit(): void
    {
        if (!$this->driver->instance()->commit()) {
            throw new \PDOException('Commit transaction failed');
        }
        $this->destruct();
    }

    /**
     * 回滚事务
     *
     * @throws \PDOException
     */
    public function rollback(): void
    {
        if (!$this->driver->instance()->rollBack()) {
            throw new \PDOException('Rollback transaction failed');
        }
        $this->destruct();
    }
}
