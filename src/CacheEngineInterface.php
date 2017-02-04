<?php

namespace ByJG\Cache;

interface CacheEngineInterface
{

    /**
     * @param string $key The object KEY
     * @param int $ttl The time to live in seconds of the object. Depends on implementation.
     * @return object The Object
     */
    public function get($key, $ttl = 0);

    /**
     * @param string $key The object Key
     * @param object $object The object to be cached
     * @param int $ttl The time to live in seconds of the object. Depends on implementation.
     * @return bool If the object is successfully posted
     */
    public function set($key, $object, $ttl = 0);

    /**
     * Append only will work with strings.
     *
     * @param string $key
     * @param string $str
     * @return bool
     */
    public function append($key, $str);

    /**
     * Lock resource before set it.
     * @param string $key
     */
    public function lock($key);

    /**
     * Unlock resource
     * @param string $key
     */
    public function unlock($key);

    /**
     * Release the object
     * @param string $key
     */
    public function release($key);

    /**
     * Return if this CacheEngine is available for use
     * @return bool
     */
    public function isAvailable();
}
