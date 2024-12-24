<?php

namespace ByJG\Cache;

use DateInterval;

interface AtomicOperationInterface
{
    public function increment(string $key, int $value = 1, DateInterval|int|null $ttl = null): int;

    public function decrement(string $key, int $value = 1, DateInterval|int|null $ttl = null): int;

    public function add(string $key, $value, DateInterval|int|null $ttl = null): array;
}