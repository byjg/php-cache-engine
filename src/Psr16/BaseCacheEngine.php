<?php

namespace ByJG\Cache\Psr16;

use ByJG\Cache\CacheAvailabilityInterface;
use ByJG\Cache\Exception\InvalidArgumentException;
use DateInterval;
use DateTime;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\CacheInterface;

abstract class BaseCacheEngine implements CacheInterface, CacheAvailabilityInterface
{
    protected ?ContainerInterface $container;

    /**
     * @param string|iterable $keys
     * @param mixed $default
     * @return iterable<string, mixed>
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getMultiple(string|iterable $keys, mixed $default = null): iterable
    {
        if (is_string($keys)) {
            $keys = [$keys];
        }

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * @param iterable $values
     * @param DateInterval|int|null $ttl
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    /**
     * @param iterable $keys
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    abstract public function isAvailable(): bool;

    protected function addToNow(DateInterval|int|null $ttl): int|null
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

    /**
     * @throws InvalidArgumentException
     */
    protected function convertToSeconds(DateInterval|int|null $ttl): DateInterval|int|null
    {
        if (empty($ttl) || is_numeric($ttl)) {
            return $ttl;
        }

        return $ttl->days*86400 + $ttl->h*3600 + $ttl->i*60 + $ttl->s;
    }


    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    protected function getKeyFromContainer(string $key): mixed
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
