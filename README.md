---
sidebar_key: cache-engine
tags: [php, databases, cache]
---

# Cache Engine

PSR-6 and PSR-16 caching over memory, files, Redis or Memcached, extended with atomic increment, decrement and compare-and-swap.

[![Sponsor](https://img.shields.io/badge/Sponsor-%23ea4aaa?logo=githubsponsors&logoColor=white&labelColor=0d1117)](https://github.com/sponsors/byjg)
[![Build Status](https://github.com/byjg/php-cache-engine/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/php-cache-engine/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](https://opensource.byjg.com)
[![Install MCP Server](https://img.shields.io/badge/Install-MCP_Server-8A2BE2?logo=modelcontextprotocol&logoColor=white)](https://opensource.byjg.com/docs/ai/mcpserver-byjg-docs/)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/php-cache-engine/)
[![GitHub license](https://img.shields.io/github/license/byjg/php-cache-engine.svg)](https://opensource.byjg.com/license/)
[![GitHub release](https://img.shields.io/github/release/byjg/php-cache-engine.svg)](https://github.com/byjg/php-cache-engine/releases/)

## Why

PSR-16 and PSR-6 cover get, set and delete, and stop there. A counter, a rate limit or a
lock needs more: with only `get()` and `set()`, two processes read the same value and one
update is lost, and checking `has()` before `set()` lets both believe they own a key.

Cache Engine implements both standards and extends them with the operations the storage
can do in one indivisible step:

```php
$cache = new RedisCacheEngine('redis-host');

$hits = $cache->increment('hits:home', 1, 60);           // atomic counter, with a TTL
$cache->decrement('stock:sku-42');                        // never loses an update

if ($cache->setIfAbsent('lock:report', $token, 60)) {    // exactly one caller gets true
    // ... only this process runs
    $cache->deleteIfEquals('lock:report', $token);       // release only our own lock
}
```

- **Atomic operations beyond PSR.** `increment()`, `decrement()` and `add()` (append to a
  list) read and modify the value in one step, on Redis, Memcached and the file system
  (`AtomicOperationInterface`).
- **Correct locks.** `setIfAbsent()`, `deleteIfEquals()` and `expireIfEquals()` compare and
  write atomically on Redis and Memcached (`CompareAndSwapInterface`); engines that cannot
  guarantee it do not offer them. [byjg/resilience](https://github.com/byjg/php-resilience)
  builds its distributed mutex on them.
- **Change the storage, keep the code.** Memory, file system, Redis, Memcached or the
  no-op engine behind the same interfaces.

## Key Features

- **PSR-16 Simple Cache interface** - Simple, straightforward caching API
- **PSR-6 Cache Pool interface** - More verbose caching with fine-grained control
- **Multiple storage backends** - Choose from memory, file system, Redis, Memcached and more
- **Atomic operations** - Support for increment, decrement and add operations in compatible engines
- **Compare and swap** - Conditional writes for correct distributed locking on Redis and Memcached
- **Garbage collection** - Automatic cleanup of expired items 
- **PSR-11 container support** - Retrieve cache keys via dependency container
- **Logging capabilities** - PSR-3 compatible logging of cache operations

## Installation

```bash
composer require "byjg/cache-engine"
```

## Quick Start

```php
// PSR-16 Simple Cache
$cache = new \ByJG\Cache\Psr16\FileSystemCacheEngine();
$cache->set('key', 'value', 3600); // Cache for 1 hour
$value = $cache->get('key');

// PSR-6 Cache Pool
$pool = \ByJG\Cache\Factory::createFilePool();
$item = $pool->getItem('key');
if (!$item->isHit()) {
    $item->set('value');
    $item->expiresAfter(3600);
    $pool->save($item);
}
$value = $item->get();
```

## Documentation

### Getting Started
- [PSR-16 Simple Cache Usage](docs/basic-usage-psr16-simplecache.md)
- [PSR-6 Cache Pool Usage](docs/basic-usage-psr6-cachepool.md)

### Available Cache Engines
| Engine                                                              | Description                                             |
|:--------------------------------------------------------------------|:--------------------------------------------------------|
| [NoCacheEngine](docs/class-no-cache-engine.md)                      | No-op engine for disabling cache without code changes   |
| [ArrayCacheEngine](docs/class-array-cache-engine.md)                | In-memory array cache (non-persistent between requests) |
| [FileSystemCacheEngine](docs/class-filesystem-cache-engine.md)      | File system based caching                               |
| [MemcachedEngine](docs/class-memcached-engine.md)                   | Memcached distributed caching                           |
| [RedisCacheEngine](docs/class-redis-cache-engine.md)                | Redis-based caching                                     |
| [SessionCacheEngine](docs/class-session-cache-engine.md)            | PHP session-based caching                               |
| [TmpfsCacheEngine](docs/class-tmpfs-cache-engine.md)                | Tmpfs-based caching                                     |

### Advanced Features
- [Atomic Operations](docs/atomic-operations.md)
- [Compare and Swap](docs/compare-and-swap.md)
- [Garbage Collection](docs/garbage-collection.md)
- [Logging](docs/setup-log-handler.md)
- [PSR-11 Container Usage](docs/psr11-usage.md)

## Running Tests

```bash
docker compose up -d
vendor/bin/phpunit
```

----
[Open source ByJG](https://opensource.byjg.com)
