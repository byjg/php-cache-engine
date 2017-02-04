<?php

namespace ByJG\Cache;

use Psr\Log\NullLogger;

class ArrayCacheEngine implements CacheEngineInterface
{

    protected $_L1Cache = array();
    
    protected $logger = null;
    
    public function __construct($logger = null)
    {
        $this->logger = $logger;
        if (is_null($logger)) {
            $this->logger = new NullLogger();
        }
    }

    /**
     * @param string $key The object KEY
     * @param int $ttl IGNORED IN MEMCACHED.
     * @return object Description
     */
    public function get($key, $ttl = 0)
    {
        

        if (array_key_exists($key, $this->_L1Cache)) {
            $this->logger->info("[Array cache] Get '$key' from L1 Cache");
            return $this->_L1Cache[$key];
        } else {
            $this->logger->info("[Array cache] Not found '$key'");
            return null;
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
        $this->logger->info("[Array cache] Set '$key' in L1 Cache");

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
        $this->logger->info("[Array cache] Append '$key' in L1 Cache");

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
