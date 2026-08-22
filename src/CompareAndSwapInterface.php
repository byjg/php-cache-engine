<?php

namespace ByJG\Cache;

use DateInterval;

/**
 * Conditional write operations that the storage engine resolves in a single indivisible step.
 *
 * Compare-and-swap means: read the current state, compare it with what the caller expects, and
 * only write when it matches - with no window in between where another process can act. Every
 * method here returns a boolean saying whether the write happened, which is the whole point: the
 * caller learns whether it won without having to issue a second query that could already be stale.
 *
 * This is what makes correct distributed locking possible on top of a cache engine. The naive
 * `has()` then `set()` sequence has a gap between the two calls where a competing process can
 * write, so both callers walk away believing they own the key.
 *
 * Implementations MUST guarantee:
 * - The comparison and the write happen atomically from the point of view of every other client.
 * - The TTL is applied in the SAME step as the write. An engine that writes first and expires
 *   afterwards leaves a key with no expiry behind if the process dies in between.
 * - Given N concurrent setIfAbsent() calls on the same absent key, exactly one returns true.
 *
 * Engines that cannot honour these guarantees MUST NOT implement this interface; callers are
 * expected to probe with `instanceof` and degrade explicitly when it is absent.
 *
 * @see AtomicOperationInterface for atomic read-modify-write of a value (increment/decrement/add).
 */
interface CompareAndSwapInterface
{
    /**
     * Store $value only if $key currently holds nothing.
     *
     * The canonical lock acquisition: the single caller that gets true owns the key until the TTL
     * elapses or it is deleted. A key whose TTL has already passed counts as absent.
     *
     * @param string $key
     * @param mixed $value
     * @param DateInterval|int|null $ttl Applied atomically with the write. Null means no expiry.
     * @return bool True when this caller performed the write; false when the key was already taken.
     */
    public function setIfAbsent(string $key, mixed $value, DateInterval|int|null $ttl = null): bool;

    /**
     * Delete $key only if it currently holds exactly $value.
     *
     * Unlike delete(), this cannot destroy a key that someone else has taken over in the meantime -
     * which is what a lock owner needs when its own TTL may have quietly expired mid-operation.
     *
     * @return bool True when the key held $value and was removed; false otherwise.
     */
    public function deleteIfEquals(string $key, mixed $value): bool;

    /**
     * Extend (or shorten) the TTL of $key only if it currently holds exactly $value.
     *
     * Lets a long-running owner keep a lock alive without ever being able to extend a lock that has
     * already passed to somebody else.
     *
     * @param DateInterval|int|null $ttl Null removes the expiry, making the key permanent.
     * @return bool True when the key held $value and its TTL was rewritten; false otherwise.
     */
    public function expireIfEquals(string $key, mixed $value, DateInterval|int|null $ttl): bool;
}
