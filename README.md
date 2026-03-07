---
tags: [php, databases, cache]
---

# Cache Engine

A powerful, versatile cache implementation providing both PSR-6 and PSR-16 interfaces with support for multiple storage drivers.

[![Sponsor](https://img.shields.io/badge/Sponsor-%23ea4aaa?logo=githubsponsors&logoColor=white&labelColor=0d1117)](https://github.com/sponsors/byjg)
[![Build Status](https://github.com/byjg/php-cache-engine/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/php-cache-engine/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](http://opensource.byjg.com)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/php-cache-engine/)
[![GitHub license](https://img.shields.io/github/license/byjg/php-cache-engine.svg)](https://opensource.byjg.com/opensource/licensing.html)
[![GitHub release](https://img.shields.io/github/release/byjg/php-cache-engine.svg)](https://github.com/byjg/php-cache-engine/releases/)

## Key Features

- **PSR-16 Simple Cache interface** - Simple, straightforward caching API
- **PSR-6 Cache Pool interface** - More verbose caching with fine-grained control
- **Multiple storage backends** - Choose from memory, file system, Redis, Memcached and more
- **Atomic operations** - Support for increment, decrement and add operations in compatible engines
- **Garbage collection** - Automatic cleanup of expired items 
- **PSR-11 container support** - Retrieve cache keys via dependency container
- **Logging capabilities** - PSR-3 compatible logging of cache operations

## Quick Start

```bash
composer require "byjg/cache-engine"
```

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
- [PSR-16 Simple Cache Usage](basic-usage-psr16-simplecache)
- [PSR-6 Cache Pool Usage](basic-usage-psr6-cachepool)

### Available Cache Engines
| Engine                                                              | Description                                             |
|:--------------------------------------------------------------------|:--------------------------------------------------------|
| [NoCacheEngine](class-no-cache-engine)                      | No-op engine for disabling cache without code changes   |
| [ArrayCacheEngine](class-array-cache-engine)                | In-memory array cache (non-persistent between requests) |
| [FileSystemCacheEngine](class-filesystem-cache-engine)      | File system based caching                               |
| [MemcachedEngine](class-memcached-engine)                   | Memcached distributed caching                           |
| [RedisCacheEngine](class-redis-cache-engine)                | Redis-based caching                                     |
| [SessionCacheEngine](class-session-cache-engine)            | PHP session-based caching                               |
| [TmpfsCacheEngine](class-tmpfs-cache-engine)                | Tmpfs-based caching                                     |
| [ShmopCacheEngine](class-shmop-cache-engine)                | Shared memory caching (deprecated)                      |
| [KeyValueCacheEngine](https://github.com/byjg/php-anydataset-nosql) | S3-Like or CloudflareKV storage (separate package)      |

### Advanced Features
- [Atomic Operations](atomic-operations)
- [Garbage Collection](garbage-collection)
- [Logging](setup-log-handler)
- [PSR-11 Container Usage](psr11-usage)

## Running Unit Tests

```
vendor/bin/phpunit --stderr
```

**Note:** The `--stderr` parameter is required for SessionCacheEngine tests to run properly.

## Dependencies

```mermaid
flowchart TD
    byjg/cache-engine --> psr/cache
    byjg/cache-engine --> psr/log
    byjg/cache-engine --> psr/simple-cache
    byjg/cache-engine --> psr/container
```

----
[Open source ByJG](http://opensource.byjg.com)
