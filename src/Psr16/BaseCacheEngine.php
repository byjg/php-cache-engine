<?php

namespace ByJG\Cache\Psr16;

use ByJG\Cache\CacheAvailabilityInterface;
use ByJG\Cache\Exception\InvalidArgumentException;
use DateInterval;
use DateTime;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

abstract class BaseCacheEngine implements CacheInterface, CacheAvailabilityInterface
{
    protected ?ContainerInterface $container;

    /**
     * @param $keys
     * @param null $default
     * @return array|iterable
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getMultiple($keys, $default = null)
    {
        if (!is_array($keys)) {
            throw new InvalidArgumentException('getMultipleKeys expected an array');
        }
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * @param iterable $values
     * @param null $ttl
     * @return bool|void
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
    }

    /**
     * @param iterable $keys
     * @return bool|void
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
    }

    abstract public function isAvailable();

    protected function addToNow($ttl)
    {
        if (is_numeric($ttl)) {
            return strtotime("+$ttl second");
        }

        if ($ttl instanceof DateInterval) {
            $now = new DateTime();
            $now->add($ttl);
            return $now->getTimestamp();
        }

        return null;
    }

    protected function convertToSeconds($ttl)
    {
        if (empty($ttl) || is_numeric($ttl)) {
            return $ttl;
        }

        if ($ttl instanceof DateInterval) {
            return $ttl->days*86400 + $ttl->h*3600 + $ttl->i*60 + $ttl->s;
        }

        throw new InvalidArgumentException('Invalid TTL');
    }


    protected function getKeyFromContainer($key)
    {
        if (empty($this->container)) {
            return $key;
        }

        if (!$this->container->has($key)) {
            throw new InvalidArgumentException("Key '$key' not found in container");
        }

        return $this->container->get($key);
    }

    public function withKeysFromContainer(?ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }
}
