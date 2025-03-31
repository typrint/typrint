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

namespace TP\Utils\Chi;

class MethodType
{
    public const int STUB = 1 << 0;
    public const int CONNECT = 1 << 1;
    public const int DELETE = 1 << 2;
    public const int GET = 1 << 3;
    public const int HEAD = 1 << 4;
    public const int OPTIONS = 1 << 5;
    public const int PATCH = 1 << 6;
    public const int POST = 1 << 7;
    public const int PUT = 1 << 8;
    public const int TRACE = 1 << 9;

    public static int $ALL;

    public static array $methodMap = [];

    public static array $reverseMethodMap = [];

    public static function init(): void
    {
        self::$methodMap = [
            'CONNECT' => self::CONNECT,
            'DELETE' => self::DELETE,
            'GET' => self::GET,
            'HEAD' => self::HEAD,
            'OPTIONS' => self::OPTIONS,
            'PATCH' => self::PATCH,
            'POST' => self::POST,
            'PUT' => self::PUT,
            'TRACE' => self::TRACE,
        ];

        self::$reverseMethodMap = array_flip(self::$methodMap);

        self::$ALL = self::CONNECT | self::DELETE | self::GET | self::HEAD |
            self::OPTIONS | self::PATCH | self::POST | self::PUT | self::TRACE;
    }

    /**
     * adds support for custom HTTP method handlers.
     */
    public static function registerMethod(string $method): void
    {
        if (empty($method)) {
            return;
        }

        $method = strtoupper($method);
        if (isset(self::$methodMap[$method])) {
            return;
        }

        $n = count(self::$methodMap);
        $mt = 2 << $n;
        self::$methodMap[$method] = $mt;
        self::$ALL |= $mt;
        self::$reverseMethodMap[$mt] = $method;
    }
}

MethodType::init();
