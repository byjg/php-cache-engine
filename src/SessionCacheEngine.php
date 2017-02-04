<?php

namespace ByJG\Cache;

class SessionCacheEngine implements CacheEngineInterface
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

    public function append($key, $str)
    {
        $this->checkSession();

        $keyName = $this->keyName($key);

        $current = $this->get($keyName);
        if ($current === false) {
            $this->set($keyName, $str);
        } else {
            $this->set($keyName, $current . $str);
        }
    }

    public function get($key, $ttl = 0)
    {
        $this->checkSession();

        $keyName = $this->keyName($key);

        if (isset($_SESSION[$keyName])) {
            return $_SESSION[$keyName];
        } else {
            return null;
        }
    }

    public function lock($key)
    {
        $this->checkSession();

        // Nothing to implement here;
    }

    public function release($key)
    {
        $this->checkSession();

        $keyName = $this->keyName($key);

        if (isset($_SESSION[$keyName])) {
            unset($_SESSION[$keyName]);
        }
    }

    public function set($key, $object, $ttl = 0)
    {
        $this->checkSession();

        $keyName = $this->keyName($key);
        $_SESSION[$keyName] = $object;
    }

    public function unlock($key)
    {
        $this->checkSession();

        // Nothing to implement here;
    }
}
