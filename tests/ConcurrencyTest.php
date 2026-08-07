<?php

namespace Tests;

use ByJG\Cache\CompareAndSwapInterface;
use ByJG\Cache\Psr16\BaseCacheEngine;
use ByJG\Cache\Psr16\MemcachedEngine;
use ByJG\Cache\Psr16\RedisCacheEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Atomicity claims are not provable from a single process - a sequential test passes just as
 * happily against code with a race in it. These tests fork real children that hammer the same key
 * at the same time, which is the only thing that actually exercises the interleavings.
 *
 * Each child reconnects after the fork: a socket inherited across fork() would be shared by every
 * child and the resulting protocol corruption would look like a race that isn't there.
 */
#[Group('concurrency')]
class ConcurrencyTest extends TestCase
{
    private const CHILDREN = 20;

    public static function engineProvider(): array
    {
        return [
            'redis' => [RedisCacheEngine::class],
            'memcached' => [MemcachedEngine::class],
        ];
    }

    #[\Override]
    protected function setUp(): void
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl is required to test concurrent access');
        }
    }

    /**
     * Runs $child in CHILDREN separate processes, released as simultaneously as we can manage, and
     * returns their exit codes. The barrier matters: without it the first child would routinely
     * finish before the last one was even forked, and nothing would ever contend.
     *
     * @return int[]
     */
    private function fork(callable $child): array
    {
        $startAt = microtime(true) + 0.5;
        $pids = [];

        for ($i = 0; $i < self::CHILDREN; $i++) {
            $pid = pcntl_fork();
            $this->assertNotSame(-1, $pid, 'Could not fork');

            if ($pid === 0) {
                $status = 1;
                try {
                    usleep((int)max(0.0, ($startAt - microtime(true)) * 1_000_000.0));
                    $status = $child($i) ? 0 : 1;
                } finally {
                    exit($status);
                }
            }

            $pids[] = $pid;
        }

        $codes = [];
        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
            $codes[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1;
        }

        return $codes;
    }

    /**
     * Always hands back a freshly connected engine, and must be called again after fork() to read
     * back results: the children inherit the parent's open socket and close it as they exit, which
     * leaves the parent's own connection dead. That is a property of forking, not of the engine.
     */
    private function engineOrSkip(string $engineClass): BaseCacheEngine
    {
        $engine = new $engineClass();
        if (!$engine->isAvailable()) {
            $this->markTestSkipped("$engineClass is not available");
        }

        return $engine;
    }

    /**
     * The defining property of setIfAbsent: N processes race for an absent key, exactly one wins.
     * A has()-then-set() implementation lets several win here.
     */
    #[DataProvider('engineProvider')]
    public function testExactlyOneProcessWinsSetIfAbsent(string $engineClass): void
    {
        $this->engineOrSkip($engineClass)->delete('race-lock');

        $codes = $this->fork(function (int $i) use ($engineClass): bool {
            /** @var CompareAndSwapInterface $engine */
            $engine = new $engineClass();
            return $engine->setIfAbsent('race-lock', "token-$i", 60);
        });

        $winners = count(array_filter($codes, fn($code) => $code === 0));
        $this->assertSame(1, $winners, "Expected exactly one winner, got $winners");
    }

    /**
     * increment() must never hand the same number to two callers. This is the test that fails
     * against a get()-then-set() seed: the losing process's set(0) lands after the winner's
     * increment and resets the counter, so the value 1 gets issued twice.
     */
    #[DataProvider('engineProvider')]
    public function testIncrementNeverIssuesTheSameValueTwice(string $engineClass): void
    {
        $this->engineOrSkip($engineClass)->delete('race-counter');

        $this->fork(function () use ($engineClass): bool {
            $engine = new $engineClass();
            $engine->increment('race-counter');
            return true;
        });

        $this->assertEquals(
            self::CHILDREN,
            $this->engineOrSkip($engineClass)->get('race-counter'),
            'Every increment must be reflected exactly once in the final counter'
        );
    }

    /**
     * Concurrent appends must all survive. A read-modify-write without a compare loses whichever
     * updates landed between another process's read and its write.
     */
    #[DataProvider('engineProvider')]
    public function testConcurrentAddLosesNothing(string $engineClass): void
    {
        $this->engineOrSkip($engineClass)->delete('race-list');

        $this->fork(function (int $i) use ($engineClass): bool {
            $engine = new $engineClass();
            $engine->add('race-list', "item-$i");
            return true;
        });

        $stored = $this->engineOrSkip($engineClass)->get('race-list');
        $this->assertIsArray($stored);
        $this->assertCount(self::CHILDREN, $stored, 'Every appended item must still be present');

        sort($stored);
        $expected = array_map(fn($i) => "item-$i", range(0, self::CHILDREN - 1));
        sort($expected);
        $this->assertEquals($expected, $stored, 'No item may be dropped or duplicated');
    }

    /**
     * The first add() to a key that set() wrote has to convert it from a plain value into a list.
     * Doing that as read-delete-rewrite means concurrent callers each delete a key the others are
     * mid-way through rebuilding, which both duplicates the original value and drops appends.
     */
    #[DataProvider('engineProvider')]
    public function testConcurrentAddSurvivesTheConversionFromAPlainValue(string $engineClass): void
    {
        $setup = $this->engineOrSkip($engineClass);
        $setup->delete('race-convert');
        $setup->set('race-convert', 'seed');

        $this->fork(function (int $i) use ($engineClass): bool {
            $engine = new $engineClass();
            $engine->add('race-convert', "item-$i");
            return true;
        });

        $stored = $this->engineOrSkip($engineClass)->get('race-convert');
        $this->assertIsArray($stored);

        sort($stored);
        $expected = array_merge(['seed'], array_map(fn($i) => "item-$i", range(0, self::CHILDREN - 1)));
        sort($expected);
        $this->assertEquals($expected, $stored, 'The seed must appear exactly once and no append may be lost');
    }

    /**
     * The whole point of a token-guarded release: only the process that actually owns the key may
     * remove it, no matter how many others try at the same instant.
     */
    #[DataProvider('engineProvider')]
    public function testOnlyTheOwnerCanDeleteUnderContention(string $engineClass): void
    {
        /** @var BaseCacheEngine&CompareAndSwapInterface $setup */
        $setup = $this->engineOrSkip($engineClass);
        $setup->delete('race-owned');
        $setup->setIfAbsent('race-owned', 'the-owner', 60);

        $codes = $this->fork(function (int $i) use ($engineClass): bool {
            /** @var CompareAndSwapInterface $engine */
            $engine = new $engineClass();
            return $engine->deleteIfEquals('race-owned', "impostor-$i");
        });

        $this->assertSame(0, count(array_filter($codes, fn($code) => $code === 0)), 'No impostor may succeed');

        $verify = $this->engineOrSkip($engineClass);
        $this->assertEquals('the-owner', $verify->get('race-owned'), 'The key must be untouched');
        $verify->delete('race-owned');
    }
}
