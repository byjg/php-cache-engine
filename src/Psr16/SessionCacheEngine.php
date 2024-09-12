<?php

namespace ByJG\Cache\Psr16;

use ByJG\Cache\Exception\InvalidArgumentException;
use DateInterval;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SessionCacheEngine extends BaseCacheEngine
{

    protected string $prefix;

    /**
     * SessionCacheEngine constructor.
     *
     * @param string $prefix
     */
    public function __construct(string $prefix = 'cache')
    {
        $this->prefix = $prefix;
    }


    protected function checkSession(): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    protected function keyName($key): string
    {
        $key = $this->getKeyFromContainer($key);
        return $this->prefix . '-' . $key;
    }

    /**
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
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

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
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

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
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

    public function isAvailable(): bool
    {
        try {
            $this->checkSession();
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }
}
