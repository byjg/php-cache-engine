<?php

namespace ByJG\Cache\Psr16;

use ByJG\Cache\Exception\InvalidArgumentException;
use DateInterval;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class NoCacheEngine extends BaseCacheEngine
{
    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->getKeyFromContainer($key);
        return $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param DateInterval|int|null $ttl
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    #[\Override]
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $key = $this->getKeyFromContainer($key);
        return true;
    }

    /**
     * @param string $key
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    #[\Override]
    public function delete(string $key): bool
    {
        $key = $this->getKeyFromContainer($key);
        return true;
    }

    /**
     * Lock resource before set it.
     * @param string $key
     */
    public function lock(string $key): void
    {
        return;
    }

    /**
     * UnLock resource after set it
     * @param string $key
     */
    public function unlock(string $key): void
    {
        return;
    }

    #[\Override]
    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    #[\Override]
    public function clear(): bool
    {
        return true;
    }

    /**
     * Determines whether an item is present in the cache.
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    #[\Override]
    public function has(string $key): bool
    {
        $key = $this->getKeyFromContainer($key);
        return false;
    }
}
