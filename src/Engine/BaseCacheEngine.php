<?php

namespace ByJG\Cache\Engine;

use ByJG\Cache\CacheAvailabilityInterface;
use Psr\SimpleCache\CacheInterface;

abstract class BaseCacheEngine implements CacheInterface, CacheAvailabilityInterface
{
    public function getMultiple($keys, $default = null)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
    }

    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
    }

    abstract public function isAvailable();
}