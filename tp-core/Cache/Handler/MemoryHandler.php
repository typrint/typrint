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

namespace TP\Cache\Handler;

class MemoryHandler implements HandlerInterface
{
    protected array $storage = [];

    public function get($key, $default = null)
    {
        $data = $this->storage[$key] ?? null;
        if (empty($data)) {
            return $default;
        }
        [$value, $expire] = $data;
        if ($expire > 0 && $expire < time()) {
            $this->delete($key);

            return $default;
        }

        return $value;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $expire = null === $ttl ? 0 : time() + $ttl;
        $data = [
            $value,
            $expire,
        ];
        $this->storage[$key] = $data;

        return true;
    }

    public function delete($key): bool
    {
        unset($this->storage[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->storage = [];

        return true;
    }

    public function has($key): bool
    {
        return array_key_exists($key, $this->storage);
    }
}
