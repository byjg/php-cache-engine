# Changelog - Version 7.0

## Overview

Version 7.0 adds `CompareAndSwapInterface`, the conditional-write primitive needed to build correct
distributed locks on top of a cache engine, and repairs three genuine race conditions in the
existing atomic operations.

The races were not theoretical. A forked-process test reproduces each one against 6.x: twenty
concurrent `increment()` calls on Memcached ended at **8** instead of 20, and twenty concurrent
`add()` calls on Redis duplicated the seed value **16 times** while dropping appends.

## Breaking Changes

| Area | Before (6.x) | After (7.0) | Description |
|------|--------------|-------------|-------------|
| **Redis atomic operations** | `INCR`/`RPUSH` + separate `EXPIRE` | Lua scripts (`EVAL`) | `increment()`, `decrement()` and `add()` are now single scripts so the value and its TTL cannot be split apart. **Requires Lua scripting to be enabled on the Redis server**; some managed offerings restrict `EVAL`. |
| **Redis `add()` TTL** | `$ttl` accepted and silently ignored | `$ttl` applied | A list built with `add($key, $value, 60)` now actually expires. Code that relied on the list living forever despite passing a TTL will see it expire. |
| **FileSystem atomic TTL** | raw `$ttl` written as the expiry timestamp | converted with `addToNow()` | `increment($key, 1, 60)` wrote `60` into the expiry file — a moment in 1970 — so the entry was already expired when written. It now means 60 seconds from now, and the value survives as intended. |

## New Features

### CompareAndSwapInterface

A new interface for conditional writes that the storage engine resolves in one indivisible step:

- `setIfAbsent(string $key, mixed $value, DateInterval|int|null $ttl = null): bool` — stores only if
  the key is free; exactly one of any number of concurrent callers gets `true`. The TTL is applied
  in the same step as the write, so a process that dies mid-operation cannot leave a key with no
  expiry behind.
- `deleteIfEquals(string $key, mixed $value): bool` — deletes only while the key still holds your
  value, so an owner whose TTL quietly lapsed cannot destroy the entry someone else has taken over.
- `expireIfEquals(string $key, mixed $value, DateInterval|int|null $ttl): bool` — extends the TTL
  under the same guard.

**Implemented by:** `RedisCacheEngine` (`SET NX EX` plus Lua for the guarded operations) and
`MemcachedEngine` (native `add()` plus `cas()`).

`FileSystemCacheEngine` deliberately does **not** implement it. `flock()` attaches to an inode
rather than a path, and this engine unlinks the file on delete, so two processes can hold "the
lock" on two different inodes at the same path. The file system is not a reliable substrate for
mutual exclusion and the engine does not pretend otherwise.

This is a separate interface rather than an addition to `AtomicOperationInterface`, so nothing that
already implements the latter breaks. Probe with `instanceof` and degrade explicitly:

```php
if (!$cache instanceof \ByJG\Cache\CompareAndSwapInterface) {
    throw new RuntimeException('This engine cannot guarantee mutual exclusion');
}
```

### Documentation

- New [Compare and Swap](docs/compare-and-swap.md) guide, including a worked lock example and an
  explicit section on what the interface does *not* give you (no fairness, no reentrancy, the TTL
  is still a guess).
- [Atomic Operations](docs/atomic-operations.md) now documents the TTL semantics and what happens
  when `add()` is called on a key previously written by `set()`.

## Bug Fixes

### Memcached: non-atomic seeding in every atomic operation

`increment()`, `decrement()` and `add()` all initialised a missing key with `get() === false`
followed by `set()`. Memcached's own `increment()` is atomic, but those two preparatory calls are
not, and the interleaving loses updates:

```
P1: get() === false          P2: get() === false
P1: set(0)
P1: increment() -> 1
                             P2: set(0)          <- resets the counter
                             P2: increment() -> 1 <- the same value issued twice
```

All three now seed with `Memcached::add()`, which the server resolves in a single step: exactly one
caller creates the key and the rest move on.

`add()` was additionally hardened — it no longer dereferences the CAS token when the key expired
mid-loop, and it claims an absent key with `add()` instead of `set()`.

### Redis: `add()` corrupted the list when converting from a plain value

The first `add()` to a key written by `set()` has to turn a string into a list. That was done as
`GET` → `DEL` → re-push → `RPUSH` with no protection, so concurrent callers each deleted a list the
others were mid-way through rebuilding. The conversion is now a compare-guarded script that only
rewrites the key while it still holds the value that was read; losing that compare means another
process already converted it.

### Redis: TTL applied as a separate command

`increment()` and `decrement()` set the expiry with a follow-up `EXPIRE`. A crash between the two
left a counter with no expiry at all. Both now apply it inside the script.

### `psr/simple-cache` 3.0 is now allowed

The engines already declare the PSR-16 3.0 signatures, but the constraint stopped at `^2.0`, so the
package could not be installed alongside anything requiring `psr/simple-cache ^3.0`. The constraint
is now `^2.0|^3.0`.

### Redis: `has()` and `clear()` never opened the connection

Both read `$this->redis` without calling `lazyLoadRedisServer()` first. Every other public method
established the connection; these two were missed. Calling either as the first operation on a new
instance died with:

```
Error: Call to a member function exists() on null
```

It went unnoticed because in practice something else — `isAvailable()`, `get()`, `set()` — almost
always ran first and left the connection open. A regression test now exercises each entry point on
an instance that has never been touched.

### FileSystem: expiry dropped outside the lock

`putContents()` deleted the `.ttl` file before acquiring the lock, leaving the value briefly
immortal — a reader arriving in that window saw an entry that should already have expired. The
delete now happens under the lock.

## Testing

- `tests/CompareAndSwapTest.php` — 18 tests across both engines covering expiry, non-owner
  rejection and the stale-owner-versus-new-owner case.
- `tests/ConcurrencyTest.php` — forks twenty real processes at a synchronised barrier. Sequential
  tests cannot prove atomicity; these fail against 6.x and pass on 7.0.
- A TTL regression test added to `tests/CachePSR16Test.php` covering every engine that implements
  `AtomicOperationInterface`.

Note for anyone writing similar tests: forked children inherit the parent's Memcached socket and
close it as they exit, killing the parent's connection. Reconnect in the parent before asserting.

## Migration Path from 6.x to 7.0

### Step 1: Confirm Lua scripting is available on your Redis server

`increment()`, `decrement()` and `add()` now use `EVAL`. This is enabled by default in Redis, but
some managed and proxied deployments restrict it. Verify with:

```bash
redis-cli EVAL "return 1" 0    # should print (integer) 1
```

If `EVAL` is unavailable in your environment, stay on 6.x or open an issue.

### Step 2: Update the dependency

```bash
composer require byjg/cache-engine:^7.0
composer update
```

### Step 3: Review any use of `add()` with a TTL on Redis

The TTL was previously ignored, so lists built this way never expired. They now do. If you were
relying on the old behaviour, drop the TTL argument:

```php
$cache->add('my-key', 'value', 3600);   // now expires after an hour
$cache->add('my-key', 'value');         // never expires, as 6.x behaved
```

### Step 4: Review any use of atomic operations with a TTL on FileSystem

These were broken and returned already-expired entries, so working code is unlikely to depend on
them. If you worked around the bug by omitting the TTL, you can now pass it.

### Step 5: Adopt compare-and-swap where you were hand-rolling a lock

If you have code shaped like this, replace it — it has a race between the two calls:

```php
// Before - two operations with a gap between them
if (!$cache->has('lock')) {
    $cache->set('lock', $token, 30);
}

// After - one indivisible operation
if ($cache->setIfAbsent('lock', $token, 30)) {
    try {
        // ...
    } finally {
        $cache->deleteIfEquals('lock', $token);
    }
}
```

### Common Migration Issues

**Issue**: `NOSCRIPT` or "unknown command EVAL" errors from Redis
**Solution**: Lua scripting is disabled or proxied away in your deployment. See Step 1.

**Issue**: Cached lists built with `add()` now disappear
**Solution**: You are passing a TTL that was previously ignored. See Step 3.

**Issue**: `$cache->setIfAbsent()` does not exist
**Solution**: The engine does not implement `CompareAndSwapInterface`. Only `RedisCacheEngine` and
`MemcachedEngine` do. Probe with `instanceof` before calling.

## Notes

- `AtomicOperationInterface` is unchanged; existing implementations continue to work untouched.
- No changes to the PSR-6 or PSR-16 interface implementations.
- Existing cache data remains compatible across versions.
- PHP requirement is now `>=8.3 <8.7` (see Requirements below).

## Links

- [Full Commit History](https://github.com/byjg/php-cache-engine/compare/6.0.1...7.0.0)
- [Documentation](https://github.com/byjg/php-cache-engine/tree/master/docs)
- [Report Issues](https://github.com/byjg/php-cache-engine/issues)

## Requirements

- PHP 8.3, 8.4, 8.5 and 8.6 are now supported: `"php": ">=8.3 <8.7"`.
  The previous `<8.6` upper bound excluded PHP 8.6, since `<8.6` is exclusive.

## Toolchain

- PHPUnit updated to `^12.5`.
- Psalm updated to `^6.16`.

  PHPUnit 13 is deliberately **not** used. It requires PHP `>=8.4.1`, which would
  break the 8.3 floor, and it pulls `sebastian/diff ^9.0`, which the newest stable
  Psalm (6.16.1) does not accept — that combination silently resolves Psalm to an
  unreleased `6.x-dev` branch. Pinning PHPUnit to `^12.5` keeps a single stable
  PHPUnit and a single stable Psalm across the whole matrix.

## Continuous Integration

- The build matrix now includes PHP 8.6.
- The Psalm job now runs on PHP 8.5. Psalm 6.16.1 declares
  `~8.1.31 || ~8.2.27 || ~8.3.16 || ~8.4.3 || ~8.5.0` and therefore cannot be
  installed on PHP 8.6.

## Housekeeping

- `phpunit.xml.dist` renamed to `phpunit.xml`.
