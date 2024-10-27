<?php

namespace ByJG\Cache\Psr16;

use ByJG\Cache\Exception\InvalidArgumentException;
use DateInterval;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Redis;
use RedisException;

class RedisCacheEngine extends BaseCacheEngine
{

    /**
     *
     * @var Redis
     */
    protected ?Redis $redis = null;

    protected LoggerInterface|null $logger = null;

    protected ?string $server = null;

    protected ?string $password = null;

    public function __construct(?string $server = null, ?string $password = null, ?LoggerInterface $logger = null)
    {
        $this->server = $server;
        if (is_null($server)) {
            $this->server = '127.0.0.1:6379';
        }

        $this->password = $password;

        $this->logger = $logger;
        if (is_null($logger)) {
            $this->logger = new NullLogger();
        }
    }

    /**
     * @throws RedisException
     */
    protected function lazyLoadRedisServer(): void
    {
        if (is_null($this->redis)) {
            $this->redis = new Redis();
            $data = explode(":", $this->server);
            $this->redis->connect($data[0], intval($data[1] ?? 6379));

            if (!empty($this->password)) {
                $this->redis->auth($this->password);
            }
            $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);

            $this->redis->info('redis_version');
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    protected function fixKey(string $key): string
    {
        $key = $this->getKeyFromContainer($key);
        return "cache:$key";
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->lazyLoadRedisServer();

        $value = $this->redis->get($this->fixKey($key));
        $this->logger->info("[Redis Cache] Get '$key' result ");

        return ($value === false ? $default : unserialize($value));
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param DateInterval|int|null $ttl
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->lazyLoadRedisServer();

        $ttl = $this->convertToSeconds($ttl);

        $this->redis->set($this->fixKey($key), serialize($value), $ttl);
        $this->logger->info("[Redis Cache] Set '$key' result ");

        return true;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedisException
     * @throws ContainerExceptionInterface
     */
    public function delete(string $key): bool
    {
        $this->lazyLoadRedisServer();

        $this->redis->del($this->fixKey($key));

        return true;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedisException
     * @throws ContainerExceptionInterface
     */
    public function clear(): bool
    {
        $keys = $this->redis->keys('cache:*');
        foreach ((array)$keys as $key) {
            if (preg_match('/^cache:(?<key>.*)/', $key, $matches)) {
                $this->delete($matches['key']);
            }
        }
        return true;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedisException
     * @throws ContainerExceptionInterface
     */
    public function has(string $key): bool
    {
        $result = $this->redis->exists($this->fixKey($key));

        if (is_numeric($result)) {
            return $result !== 0;
        }

        if ($result instanceof Redis) {
            return true;
        }

        return $result;
    }

    public function isAvailable(): bool
    {
        if (!class_exists('\Redis')) {
            return false;
        }

        try {
            $this->lazyLoadRedisServer();
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }
}
