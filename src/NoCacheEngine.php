<?php

namespace ByJG\Cache;

class NoCacheEngine implements CacheEngineInterface
{

    /**
     * @param string $key The object KEY
     * @param int $ttl IGNORED IN MEMCACHED.
     * @return object Description
     */
    public function get($key, $ttl = 0)
    {
        return null;
    }

    /**
     * @param string $key The object Key
     * @param object $object The object to be cached
     * @param int $ttl The time to live in seconds of this objects
     * @return bool If the object is successfully posted
     */
    public function set($key, $object, $ttl = 0)
    {
        return true;
    }

    /**
     * Unlock resource
     * @param string $key
     */
    public function release($key)
    {
        return;
    }

    /**
     *
     * @param string $key
     * @param string $str
     * @return bool
     */
    public function append($key, $str)
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
}
