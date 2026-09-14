---
sidebar_position: 12
---

# Compare and Swap

Compare-and-swap means: read the current state, compare it against what you expect, and only write
when it matches — all resolved by the storage engine in a single indivisible step.

The reason this needs its own interface is that the obvious way of writing it is wrong:

```php
<?php
// WRONG - two separate operations
if (!$cache->has('lock')) {   // <- another process can write here
    $cache->set('lock', $token, 30);
}
```

Between the `has()` and the `set()` there is a gap. Two processes can both find the key absent and
both write, and both walk away believing they own it. No amount of re-reading afterwards closes
that gap. Compare-and-swap removes it by never splitting the check from the write.

The engines that support it implement `CompareAndSwapInterface`.

**Engines that support compare and swap:**

- RedisCacheEngine
- MemcachedEngine

FileSystemCacheEngine does **not** implement this interface. See
[the note at the bottom](#why-not-filesystemcacheengine).

## setIfAbsent

Stores the value only if the key currently holds nothing. Exactly one of any number of concurrent
callers gets `true`. A key whose TTL has already elapsed counts as absent.

```php
<?php
/** @var \ByJG\Cache\CompareAndSwapInterface $cache */
$token = bin2hex(random_bytes(16));

if ($cache->setIfAbsent('my-lock', $token, 30)) {
    // This process, and only this process, owns 'my-lock' for the next 30 seconds
}
```

The TTL is applied in the same step as the write. This matters: an engine that wrote the value
first and set the expiry afterwards would leave a key with no expiry at all if the process died in
between — a lock nobody can ever release.

## deleteIfEquals

Deletes the key only if it still holds exactly the value you pass.

```php
<?php
/** @var \ByJG\Cache\CompareAndSwapInterface $cache */
$cache->deleteIfEquals('my-lock', $token);
```

Use this instead of `delete()` whenever the key might have changed hands. If your TTL quietly
expired while you were still working, another process may already hold the key — a plain `delete()`
would destroy *their* lock. `deleteIfEquals()` refuses and returns `false`.

## expireIfEquals

Rewrites the TTL, but only while the key still holds your value. This lets a long-running owner
keep its claim alive without any risk of extending a claim that has already passed to someone else.

```php
<?php
/** @var \ByJG\Cache\CompareAndSwapInterface $cache */
while ($stillWorking) {
    // ... do a chunk of work ...
    if (!$cache->expireIfEquals('my-lock', $token, 30)) {
        // We lost the lock. Stop - somebody else is doing this work now.
        break;
    }
}
```

Passing `null` as the TTL removes the expiry, making the key permanent.

## Putting it together

```php
<?php
/** @var \ByJG\Cache\CompareAndSwapInterface $cache */
$token = bin2hex(random_bytes(16));

if (!$cache->setIfAbsent('rebuild-report', $token, 60)) {
    return; // Another worker already has it
}

try {
    rebuildTheReport();
} finally {
    $cache->deleteIfEquals('rebuild-report', $token);
}
```

The random token is what makes the release safe. Without it there is no way to tell your own lock
apart from the one a later process took over after your TTL lapsed.

## Detecting support

Not every engine can offer these guarantees. Probe before relying on them, and degrade explicitly
rather than silently:

```php
<?php
if (!$cache instanceof \ByJG\Cache\CompareAndSwapInterface) {
    throw new RuntimeException('This engine cannot guarantee mutual exclusion');
}
```

## What this does not give you

Compare-and-swap makes acquisition correct. It does not turn the cache into a complete lock
manager. There is no fairness (a starved process can lose every round), no reentrancy, and the TTL
is still a guess: a slow owner can have its lock expire while it is still working. `deleteIfEquals`
will correctly refuse that owner's release, but the work has already run twice. If you need
guarantees beyond "exactly one winner per acquisition", you want a real lock manager.

## Why not FileSystemCacheEngine {#why-not-filesystemcacheengine}

The file system is not a reliable substrate for mutual exclusion, and this engine does not pretend
otherwise. Use Redis or Memcached.

`flock()` attaches to an inode, not to a path. Because this engine deletes a cache file when the
entry is removed, a process can end up holding a lock on a file that has already been unlinked
while another process creates and locks a fresh file at the same path — two processes holding
"the lock" on two different inodes, both proceeding. On network file systems `flock()` is weaker
still, and on some NFS configurations it is advisory in name only.

`increment`, `decrement` and `add` remain available here through
[`AtomicOperationInterface`](atomic-operations.md), and are safe against other callers going
through the same engine on the same host. That is a narrower guarantee than compare-and-swap, and
it is not a lock.
