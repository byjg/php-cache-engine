<?php

namespace ByJG\Cache\Psr16;

use ByJG\Cache\AtomicOperationInterface;
use ByJG\Cache\Exception\InvalidArgumentException;
use DateInterval;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Redis;
use RedisException;

class RedisCacheEngine extends BaseCacheEngine implements AtomicOperationInterface
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
    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        $this->lazyLoadRedisServer();

        $fixKey = $this->fixKey($key);
        $type = $this->redis->type($fixKey);

        if ($type === Redis::REDIS_STRING) {
            $value = $this->redis->get($fixKey);
            if (is_string($value) && preg_match('/^[Oa]:\d+:["{]/', $value)) {
                $value = unserialize($value);
            }
        } else if ($type === Redis::REDIS_LIST) {
            $value = $this->redis->lRange($fixKey, 0, -1);
        } else {
            $value = $default;
        }

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if (is_string($v) && preg_match('/^[Oa]:\d+:["{]/', $v)) {
                    $value[$k] = unserialize($v);
                }
            }
        }

        return $value;
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
    #[\Override]
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->lazyLoadRedisServer();

        $ttl = $this->convertToSeconds($ttl);

        $this->redis->set($this->fixKey($key), is_object($value) || is_array($value) ? serialize($value) : $value, $ttl);
        $this->logger->info("[Redis Cache] Set '$key' result ");

        return true;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedisException
     * @throws ContainerExceptionInterface
     */
    #[\Override]
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
    #[\Override]
    public function clear(): bool
    {
        $keys = $this->redis->keys('cache:*');
        foreach ($keys as $key) {
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
    #[\Override]
    public function has(string $key): bool
    {
        $result = $this->redis->exists($this->fixKey($key));
        return (bool)$result;
    }

    #[\Override]
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

    #[\Override]
    public function increment(string $key, int $value = 1, DateInterval|int|null $ttl = null): int
    {
        $this->lazyLoadRedisServer();

        $result = $this->redis->incr($this->fixKey($key), $value);

        if ($ttl) {
            $this->redis->expire($this->fixKey($key), $this->convertToSeconds($ttl));
        }

        return $result;
    }

    #[\Override]
    public function decrement(string $key, int $value = 1, DateInterval|int|null $ttl = null): int
    {
        $this->lazyLoadRedisServer();

        $result = $this->redis->decr($this->fixKey($key), $value);

        if ($ttl) {
            $this->redis->expire($this->fixKey($key), $this->convertToSeconds($ttl));
        }

        return $result;
    }

    #[\Override]
    public function add(string $key, $value, DateInterval|int|null $ttl = null): array
    {
        $this->lazyLoadRedisServer();

        $fixKey = $this->fixKey($key);
        $type = $this->redis->type($fixKey);

        if ($type === Redis::REDIS_STRING) {
            $currValue = $this->redis->get($fixKey);
            if (is_string($currValue) && preg_match('/^[Oa]:\d+:["{]/', $currValue)) {
                $currValue = unserialize($currValue);
            }
            if (is_object($currValue)) {
                $currValue = [$currValue];
            }
            $this->redis->del($fixKey);
            foreach ((array)$currValue as $items) {
                $this->add($key, $items);
            }
        }

        $result = $this->redis->rPush($fixKey, is_object($value) || is_array($value) ? serialize($value) : $value);

        return $result ? $this->get($key) : [];
    }
}
