<?php

namespace ByJG\Cache\Psr16;

use ByJG\Cache\AtomicOperationInterface;
use ByJG\Cache\CompareAndSwapInterface;
use ByJG\Cache\Exception\InvalidArgumentException;
use ByJG\Cache\Exception\StorageErrorException;
use DateInterval;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Redis;
use RedisException;

class RedisCacheEngine extends BaseCacheEngine implements AtomicOperationInterface, CompareAndSwapInterface
{
    /**
     * Every multi-step operation below runs as a Lua script instead of a sequence of commands.
     * Redis executes a script to completion before serving any other client, so the read and the
     * write it performs cannot be interleaved - which is exactly the guarantee the callers need.
     */
    private const LUA_INCREMENT_BY = <<<'LUA'
        local result = redis.call('INCRBY', KEYS[1], ARGV[1])
        if tonumber(ARGV[2]) > 0 then
            redis.call('EXPIRE', KEYS[1], ARGV[2])
        end
        return result
        LUA;

    /** Appends to the list and hands back the result the caller would otherwise have to re-read. */
    private const LUA_APPEND = <<<'LUA'
        if redis.call('TYPE', KEYS[1])['ok'] == 'string' then
            return false
        end
        redis.call('RPUSH', KEYS[1], ARGV[1])
        if tonumber(ARGV[2]) > 0 then
            redis.call('EXPIRE', KEYS[1], ARGV[2])
        end
        return redis.call('LRANGE', KEYS[1], 0, -1)
        LUA;

    /** Rewrites a string key as a list, but only while it still holds the value we inspected. */
    private const LUA_EXPLODE_STRING = <<<'LUA'
        if redis.call('GET', KEYS[1]) ~= ARGV[1] then
            return 0
        end
        redis.call('DEL', KEYS[1])
        for i = 2, #ARGV do
            redis.call('RPUSH', KEYS[1], ARGV[i])
        end
        return 1
        LUA;

    private const LUA_DELETE_IF_EQUALS = <<<'LUA'
        if redis.call('GET', KEYS[1]) == ARGV[1] then
            return redis.call('DEL', KEYS[1])
        end
        return 0
        LUA;

    private const LUA_EXPIRE_IF_EQUALS = <<<'LUA'
        if redis.call('GET', KEYS[1]) ~= ARGV[1] then
            return 0
        end
        if tonumber(ARGV[2]) > 0 then
            redis.call('EXPIRE', KEYS[1], ARGV[2])
        else
            redis.call('PERSIST', KEYS[1])
        end
        return 1
        LUA;

    /** Bound on the string-to-list conversion retry; only a pathological writer ever gets close. */
    private const MAX_CONVERSION_ATTEMPTS = 10;

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
     * Redis stores scalars verbatim and everything else serialized. get(), add() and the
     * compare-and-swap operations must agree on this representation, otherwise a CAS comparison
     * would be made against a string the caller never actually wrote.
     */
    protected function encode(mixed $value): mixed
    {
        return is_object($value) || is_array($value) ? serialize($value) : $value;
    }

    protected function decode(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^[Oa]:\d+:["{]/', $value)) {
            return unserialize($value);
        }

        return $value;
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
            $value = $this->decode($this->redis->get($fixKey));
        } else if ($type === Redis::REDIS_LIST) {
            $value = $this->redis->lRange($fixKey, 0, -1);
        } else {
            $value = $default;
        }

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->decode($v);
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
        $iterator = null;
        do {
            $keys = $this->redis->scan($iterator, 'cache:*');
            foreach($keys as $key) {
                $this->delete(substr($key, 6));
            }
        } while($iterator !== 0);
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
        return $this->incrementBy($key, $value, $ttl);
    }

    #[\Override]
    public function decrement(string $key, int $value = 1, DateInterval|int|null $ttl = null): int
    {
        return $this->incrementBy($key, -$value, $ttl);
    }

    /**
     * INCRBY covers both directions. Applying the TTL inside the script matters: as two separate
     * commands, a process that died in between would leave the counter with no expiry at all.
     */
    private function incrementBy(string $key, int $delta, DateInterval|int|null $ttl): int
    {
        return (int)$this->script(self::LUA_INCREMENT_BY, $key, [$delta, $this->ttlInSeconds($ttl)]);
    }

    /**
     * Run one of the scripts above against a single key.
     *
     * Every script here takes exactly one key and reads the rest of its inputs from ARGV, so the
     * lazy connect and the eval() shape are the same each time and belong in one place.
     */
    private function script(string $lua, string $key, array $args = []): mixed
    {
        $this->lazyLoadRedisServer();

        return $this->redis->eval($lua, array_merge([$this->fixKey($key)], $args), 1);
    }

    /**
     * @throws StorageErrorException
     */
    #[\Override]
    public function add(string $key, $value, DateInterval|int|null $ttl = null): array
    {
        $args = [$this->encode($value), $this->ttlInSeconds($ttl)];

        // A key previously written by set() is a plain string and has to become a list before it
        // can be appended to. The append itself never contends - the loop only exists so a caller
        // that loses that one-shot conversion to a concurrent writer can pick up and carry on.
        for ($attempt = 0; $attempt < self::MAX_CONVERSION_ATTEMPTS; $attempt++) {
            $list = $this->script(self::LUA_APPEND, $key, $args);

            if (is_array($list)) {
                return array_map(fn($item) => $this->decode($item), $list);
            }

            $this->convertStringToList($key);
        }

        throw new StorageErrorException("Could not append to '$key': the key kept changing type");
    }

    /**
     * Turn a string key into the list add() appends to, without ever exposing a moment where the
     * key is missing. The script rewrites it only while it still holds the value we just read, so
     * losing the race means another process already converted it and there is nothing left to do.
     */
    private function convertStringToList(string $key): void
    {
        $current = $this->redis->get($this->fixKey($key));
        if ($current === false) {
            return; // Expired or deleted meanwhile; the next append creates a fresh list.
        }

        $decoded = $this->decode($current);
        $elements = is_array($decoded) ? array_values($decoded) : [$decoded];

        $this->script(
            self::LUA_EXPLODE_STRING,
            $key,
            array_merge([$current], array_map(fn($item) => $this->encode($item), $elements))
        );
    }

    #[\Override]
    public function setIfAbsent(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->lazyLoadRedisServer();

        $seconds = $this->ttlInSeconds($ttl);
        // SET NX writes the value and its expiry as one command - there is no window in which the
        // key exists without a TTL, so a crash here cannot leave the key behind forever.
        $options = $seconds > 0 ? ['nx', 'ex' => $seconds] : ['nx'];

        return $this->redis->set($this->fixKey($key), $this->encode($value), $options) !== false;
    }

    #[\Override]
    public function deleteIfEquals(string $key, mixed $value): bool
    {
        return (bool)$this->script(self::LUA_DELETE_IF_EQUALS, $key, [$this->encode($value)]);
    }

    #[\Override]
    public function expireIfEquals(string $key, mixed $value, DateInterval|int|null $ttl): bool
    {
        return (bool)$this->script(
            self::LUA_EXPIRE_IF_EQUALS,
            $key,
            [$this->encode($value), $this->ttlInSeconds($ttl)]
        );
    }

    /** Lua has no notion of "no TTL", so the absence of one is passed down as a plain zero. */
    private function ttlInSeconds(DateInterval|int|null $ttl): int
    {
        $seconds = $this->convertToSeconds($ttl);

        return is_int($seconds) ? $seconds : 0;
    }
}
