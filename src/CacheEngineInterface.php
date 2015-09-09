<?php

namespace ByJG\Cache;

interface CacheEngineInterface
{

    /**
     * @param string $key The object KEY
     * @param int $ttl The time to live in seconds of the object. Depends on implementation.
     * @return object The Object
     */
    function get($key, $ttl = 0);

    /**
     * @param string $key The object Key
     * @param object $object The object to be cached
     * @param int $ttl The time to live in seconds of the object. Depends on implementation.
     * @return bool If the object is successfully posted
     */
    function set($key, $object, $ttl = 0);

    /**
     * Append only will work with strings.
     *
     * @param string $key
     * @param string $str
     * @return bool
     */
    function append($key, $str);

    /**
     * Lock resource before set it.
     * @param string $key
     */
    function lock($key);

    /**
     * Unlock resource
     * @param string $key
     */
    function unlock($key);

    /**
     * Release the object
     * @param string $key
     */
    function release($key);
}
