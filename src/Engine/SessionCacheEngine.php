<?php

namespace ByJG\Cache\Engine;

class SessionCacheEngine extends BaseCacheEngine
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

        if ($this->has($key)) {
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
        if (isset($_SESSION["$keyName.ttl"])) {
            unset($_SESSION["$keyName.ttl"]);
        }
    }

    public function set($key, $value, $ttl = null)
    {
        $this->checkSession();

        $keyName = $this->keyName($key);
        $_SESSION[$keyName] = $value;
        if (!empty($ttl)) {
            $_SESSION["$keyName.ttl"] = $this->addToNow($ttl);
        }
    }

    public function clear()
    {
        session_destroy();
    }

    public function has($key)
    {
        $keyName = $this->keyName($key);

        if (isset($_SESSION[$keyName])) {
            if (isset($_SESSION["$keyName.ttl"]) && time() >= $_SESSION["$keyName.ttl"]) {
                $this->delete($key);
                return false;
            }

            return true;
        }

        return false;
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
