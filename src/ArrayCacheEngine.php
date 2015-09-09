<?php

namespace ByJG\Cache;

class ArrayCacheEngine implements CacheEngineInterface
{

    protected $_L1Cache = array();

    /**
     * @param string $key The object KEY
     * @param int $ttl IGNORED IN MEMCACHED.
     * @return object Description
     */
    public function get($key, $ttl = 0)
    {
        $log = LogHandler::getInstance();

        if (array_key_exists($key, $this->_L1Cache)) {
            $log->info("[Cache] Get '$key' from L1 Cache");
            return $this->_L1Cache[$key];
        } else {
            $log->info("[Cache] Not found '$key'");
            return false;
        }
    }

    /**
     * @param string $key The object Key
     * @param object $object The object to be cached
     * @param int $ttl The time to live in seconds of this objects
     * @return bool If the object is successfully posted
     */
    public function set($key, $object, $ttl = 0)
    {
        $log = LogHandler::getInstance();
        $log->info("[Cache] Set '$key' in L1 Cache");

        $this->_L1Cache[$key] = $object;

        return true;
    }

    /**
     *
     * @param string $key
     * @param string $str
     * @return bool
     */
    public function append($key, $str)
    {
        $log = LogHandler::getInstance();
        $log->info("[Cache] Append '$key' in L1 Cache");

        $this->_L1Cache[$key] = $this->_L1Cache[$key] . $str;

        return true;
    }

    /**
     * Unlock resource
     * @param string $key
     */
    public function release($key)
    {
        unset($this->_L1Cache[$key]);
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
