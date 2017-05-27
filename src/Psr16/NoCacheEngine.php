<?php

namespace ByJG\Cache\Psr16;

use ByJG\Cache\CacheLockInterface;

class NoCacheEngine extends BaseCacheEngine implements CacheLockInterface
{
    /**
     * @param string $key The object KEY
     * @param int $default IGNORED IN MEMCACHED.
     * @return mixed Description
     */
    public function get($key, $default = null)
    {
        return $default;
    }

    /**
     * @param string $key The object Key
     * @param object $value The object to be cached
     * @param int $ttl The time to live in seconds of this objects
     * @return bool If the object is successfully posted
     */
    public function set($key, $value, $ttl = 0)
    {
        return true;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        return true;
    }

    /**
     * Lock resource before set it.
     * @param string $key
     */
    public function lock($key)
    {
        return;
    }

    /**
     * UnLock resource after set it
     * @param string $key
     */
    public function unlock($key)
    {
        return;
    }

    public function isAvailable()
    {
        return true;
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear()
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
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
    */
    public function has($key)
    {
        return false;
    }
}
