<?php

namespace ByJG\Cache\Engine;

use ByJG\Cache\CacheAvailabilityInterface;
use ByJG\Cache\CacheLockInterface;

class SessionCacheEngine extends BaseCacheEngine implements CacheAvailabilityInterface
{

    protected $prefix = null;

    /**
     * SessionCacheEngine constructor.
     *
     * @param string $prefix
     */
    public function __construct($prefix = 'cache')
    {
        $this->prefix = $prefix;
    }


    protected function checkSession()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function keyName($key)
    {
        return $this->prefix . '-' . $key;
    }

    public function get($key, $default = null)
    {
        $this->checkSession();

        $keyName = $this->keyName($key);

        if (isset($_SESSION[$keyName])) {
            return $_SESSION[$keyName];
        } else {
            return $default;
        }
    }

    public function delete($key)
    {
        $this->checkSession();

        $keyName = $this->keyName($key);

        if (isset($_SESSION[$keyName])) {
            unset($_SESSION[$keyName]);
        }
    }

    public function set($key, $value, $ttl = 0)
    {
        $this->checkSession();

        $keyName = $this->keyName($key);
        $_SESSION[$keyName] = $value;
    }

    public function clear()
    {
        session_destroy();
    }

    public function has($key)
    {
        $keyName = $this->keyName($key);

        return (isset($_SESSION[$keyName]));
    }

    public function isAvailable()
    {
        try {
            $this->checkSession();
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }
}
