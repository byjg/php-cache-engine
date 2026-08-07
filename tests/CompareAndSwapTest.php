<?php

namespace Tests;

use ByJG\Cache\CompareAndSwapInterface;
use ByJG\Cache\Psr16\BaseCacheEngine;
use ByJG\Cache\Psr16\MemcachedEngine;
use ByJG\Cache\Psr16\RedisCacheEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CompareAndSwapTest extends TestCase
{
    /** @var BaseCacheEngine[] */
    private array $started = [];

    public static function engineProvider(): array
    {
        return [
            'redis' => [new RedisCacheEngine()],
            'memcached' => [new MemcachedEngine()],
        ];
    }

    #[\Override]
    protected function tearDown(): void
    {
        foreach ($this->started as $engine) {
            $engine->delete('cas-key');
        }
        $this->started = [];
    }

    /**
     * Skips instead of failing so the suite still runs without the docker-compose services up,
     * which is how the rest of this test suite treats an unreachable backend.
     */
    private function engineOrSkip(BaseCacheEngine $engine): CompareAndSwapInterface
    {
        if (!$engine->isAvailable()) {
            $this->markTestSkipped(get_class($engine) . ' is not available');
        }

        $this->assertInstanceOf(CompareAndSwapInterface::class, $engine);

        $this->started[] = $engine;
        $engine->delete('cas-key');

        return $engine;
    }

    #[DataProvider('engineProvider')]
    public function testSetIfAbsentSucceedsOnlyForTheFirstCaller(BaseCacheEngine $engine): void
    {
        $cas = $this->engineOrSkip($engine);

        $this->assertTrue($cas->setIfAbsent('cas-key', 'token-a', 30));
        $this->assertFalse($cas->setIfAbsent('cas-key', 'token-b', 30));
        $this->assertEquals('token-a', $engine->get('cas-key'), 'The loser must not have overwritten the winner');
    }

    #[DataProvider('engineProvider')]
    public function testSetIfAbsentSucceedsAgainOnceTheTtlHasPassed(BaseCacheEngine $engine): void
    {
        $cas = $this->engineOrSkip($engine);

        $this->assertTrue($cas->setIfAbsent('cas-key', 'token-a', 1));
        $this->assertFalse($cas->setIfAbsent('cas-key', 'token-b', 1));

        sleep(2);

        $this->assertTrue($cas->setIfAbsent('cas-key', 'token-b', 30));
        $this->assertEquals('token-b', $engine->get('cas-key'));
    }

    #[DataProvider('engineProvider')]
    public function testSetIfAbsentAppliesTheTtlWithTheWrite(BaseCacheEngine $engine): void
    {
        $cas = $this->engineOrSkip($engine);

        $cas->setIfAbsent('cas-key', 'token-a', 1);
        sleep(2);

        $this->assertNull($engine->get('cas-key'), 'The key must expire on its own, with no second command');
    }

    #[DataProvider('engineProvider')]
    public function testDeleteIfEqualsOnlyRemovesTheMatchingValue(BaseCacheEngine $engine): void
    {
        $cas = $this->engineOrSkip($engine);

        $cas->setIfAbsent('cas-key', 'token-a', 30);

        $this->assertFalse($cas->deleteIfEquals('cas-key', 'token-b'));
        $this->assertEquals('token-a', $engine->get('cas-key'), 'A non-matching caller must not delete');

        $this->assertTrue($cas->deleteIfEquals('cas-key', 'token-a'));
        $this->assertNull($engine->get('cas-key'));
    }

    #[DataProvider('engineProvider')]
    public function testDeleteIfEqualsOnAMissingKeyReturnsFalse(BaseCacheEngine $engine): void
    {
        $cas = $this->engineOrSkip($engine);

        $this->assertFalse($cas->deleteIfEquals('cas-key', 'token-a'));
    }

    /**
     * The scenario the interface exists for: an owner whose TTL quietly lapsed must not be able to
     * delete the key that somebody else has legitimately taken over in the meantime.
     */
    #[DataProvider('engineProvider')]
    public function testAnExpiredOwnerCannotDeleteTheNewOwnersValue(BaseCacheEngine $engine): void
    {
        $cas = $this->engineOrSkip($engine);

        $cas->setIfAbsent('cas-key', 'stale-owner', 1);
        sleep(2);
        $this->assertTrue($cas->setIfAbsent('cas-key', 'new-owner', 30));

        $this->assertFalse($cas->deleteIfEquals('cas-key', 'stale-owner'));
        $this->assertFalse($cas->expireIfEquals('cas-key', 'stale-owner', 30));
        $this->assertEquals('new-owner', $engine->get('cas-key'));
    }

    #[DataProvider('engineProvider')]
    public function testExpireIfEqualsExtendsTheLifetimeOfAMatchingValue(BaseCacheEngine $engine): void
    {
        $cas = $this->engineOrSkip($engine);

        $cas->setIfAbsent('cas-key', 'token-a', 1);
        $this->assertTrue($cas->expireIfEquals('cas-key', 'token-a', 30));

        sleep(2);

        $this->assertEquals('token-a', $engine->get('cas-key'), 'The original 1s expiry must have been replaced');
    }

    #[DataProvider('engineProvider')]
    public function testExpireIfEqualsIsRejectedForANonMatchingValue(BaseCacheEngine $engine): void
    {
        $cas = $this->engineOrSkip($engine);

        $cas->setIfAbsent('cas-key', 'token-a', 1);
        $this->assertFalse($cas->expireIfEquals('cas-key', 'token-b', 30));

        sleep(2);

        $this->assertNull($engine->get('cas-key'), 'The rejected refresh must not have extended anything');
    }

    #[DataProvider('engineProvider')]
    public function testSetIfAbsentRoundTripsNonScalarValues(BaseCacheEngine $engine): void
    {
        $cas = $this->engineOrSkip($engine);

        $this->assertTrue($cas->setIfAbsent('cas-key', ['a' => 1, 'b' => 2], 30));
        $this->assertEquals(['a' => 1, 'b' => 2], $engine->get('cas-key'));

        $this->assertFalse($cas->deleteIfEquals('cas-key', ['a' => 1]));
        $this->assertTrue($cas->deleteIfEquals('cas-key', ['a' => 1, 'b' => 2]));
    }
}
