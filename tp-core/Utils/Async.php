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

use Swow\Coroutine;

class Async
{
    protected Coroutine $coroutine;

    public function __construct(?callable $callable = null)
    {
        if (null === $callable) {
            return;
        }

        $this->coroutine = new Coroutine($callable);
    }

    /**
     * Create a new async instance and run it immediately.
     *
     * @param callable $callable the coroutine body
     * @param mixed    ...$data  arguments passed to the callable
     *
     * @return static the new created async instance
     */
    public static function run(callable $callable, mixed ...$data): static
    {
        $async = new static($callable);
        $async->resume(...$data);

        return $async;
    }

    public static function yield(mixed $data = null): mixed
    {
        return Coroutine::yield($data);
    }

    /** Get unique coroutine id */
    public static function id(): int
    {
        return Coroutine::getCurrent()->getId();
    }

    public static function count(): int
    {
        return Coroutine::count();
    }

    public static function get(int $id): ?static
    {
        $coroutine = Coroutine::get($id);
        if (null === $coroutine) {
            return null;
        }

        $instance = new static();
        $instance->coroutine = $coroutine;

        return $instance;
    }

    public static function current(): ?static
    {
        try {
            $coroutine = Coroutine::getCurrent();
        } catch (\Throwable) {
            return null;
        }

        $instance = new static();
        $instance->coroutine = $coroutine;

        return $instance;
    }

    public static function list(): iterable
    {
        yield from array_keys(Coroutine::getAll());
    }

    /**
     * Resume execution.
     */
    public function resume(mixed ...$data): mixed
    {
        return $this->coroutine->resume(...$data);
    }

    public function executing(): bool
    {
        return $this->coroutine->isExecuting();
    }

    public function kill(): void
    {
        $this->coroutine->kill();
    }
}
