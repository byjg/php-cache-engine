<?php

namespace ByJG\Cache\Psr16;

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
        $key = $this->getKeyFromContainer($key);
        return $this->prefix . '-' . $key;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->checkSession();

        $keyName = $this->keyName($key);

        if ($this->has($key)) {
            return $_SESSION[$keyName];
        } else {
            return $default;
        }
    }

    public function delete(string $key): bool
    {
        $this->checkSession();

        $keyName = $this->keyName($key);

        if (isset($_SESSION[$keyName])) {
            unset($_SESSION[$keyName]);
        }
        if (isset($_SESSION["$keyName.ttl"])) {
            unset($_SESSION["$keyName.ttl"]);
        }

        return true;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->checkSession();

        $keyName = $this->keyName($key);
        $_SESSION[$keyName] = $value;
        if (!empty($ttl)) {
            $_SESSION["$keyName.ttl"] = $this->addToNow($ttl);
        }

        return true;
    }

    public function clear(): bool
    {
        session_destroy();
        return true;
    }

    public function has(string $key): bool
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
