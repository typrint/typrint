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

/**
 * Factory class for creating contexts.
 */
class Context
{
    /**
     * Returns an empty context that is never canceled.
     */
    public static function background(): ContextInterface
    {
        return new BackgroundContext();
    }

    /**
     * Returns a context that is canceled when the returned cancel function is called.
     *
     * @return array{0: ContextInterface, 1: callable} [context, cancelFunc]
     */
    public static function withCancel(ContextInterface $parent): array
    {
        $ctx = new CancelContext($parent);
        $cancel = function () use ($ctx) {
            $ctx->cancel();
        };

        return [$ctx, $cancel];
    }

    /**
     * Returns a context that is canceled when the deadline is reached.
     *
     * @return array{0: ContextInterface, 1: callable} [context, cancelFunc]
     */
    public static function withDeadline(ContextInterface $parent, \DateTimeInterface $deadline): array
    {
        $ctx = new DeadlineContext($parent, $deadline);
        $cancel = function () use ($ctx) {
            $ctx->cancel();
        };

        return [$ctx, $cancel];
    }

    /**
     * Returns a context that is canceled after the specified duration.
     *
     * @return array{0: ContextInterface, 1: callable} [context, cancelFunc]
     */
    public static function withTimeout(ContextInterface $parent, int $seconds): array
    {
        $deadline = new \DateTime();
        $deadline->add(new \DateInterval("PT{$seconds}S"));

        return self::withDeadline($parent, $deadline);
    }

    /**
     * Returns a context with the value associated with the key.
     */
    public static function withValue(ContextInterface $parent, string $key, mixed $value): ContextInterface
    {
        return new ValueContext($parent, $key, $value);
    }
}
