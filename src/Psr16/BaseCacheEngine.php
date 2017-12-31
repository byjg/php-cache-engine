<?php

namespace ByJG\Cache\Psr16;

use ByJG\Cache\CacheAvailabilityInterface;
use ByJG\Cache\InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

abstract class BaseCacheEngine implements CacheInterface, CacheAvailabilityInterface
{
    /**
     * @param $keys
     * @param null $default
     * @return array|iterable
     * @throws \ByJG\Cache\InvalidArgumentException
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

        if ($ttl instanceof \DateInterval) {
            $now = new \DateTime();
            $now->add($ttl);
            return $now->getTimestamp();
        }

        return null;
    }
}