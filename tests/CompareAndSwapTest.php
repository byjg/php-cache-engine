<?php

namespace Tests;

use ByJG\Cache\CompareAndSwapInterface;
use ByJG\Cache\Psr16\BaseCacheEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CompareAndSwapTest extends TestCase
{
    /** @var BaseCacheEngine[] */
    private array $started = [];

    public static function engineProvider(): array
    {
        return [
            'redis' => [EngineFactory::REDIS],
            'memcached' => [EngineFactory::MEMCACHED],
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
    private function engineOrSkip(string $engineName): BaseCacheEngine&CompareAndSwapInterface
    {
        $engine = EngineFactory::make($engineName);
        if (!$engine->isAvailable()) {
            $this->markTestSkipped(get_class($engine) . ' is not available');
        }

        $this->started[] = $engine;
        $engine->delete('cas-key');

        return $engine;
    }

    #[DataProvider('engineProvider')]
    public function testSetIfAbsentSucceedsOnlyForTheFirstCaller(string $engineName): void
    {
        $engine = $this->engineOrSkip($engineName);

        $this->assertTrue($engine->setIfAbsent('cas-key', 'token-a', 30));
        $this->assertFalse($engine->setIfAbsent('cas-key', 'token-b', 30));
        $this->assertEquals('token-a', $engine->get('cas-key'), 'The loser must not have overwritten the winner');
    }

    #[DataProvider('engineProvider')]
    public function testSetIfAbsentSucceedsAgainOnceTheTtlHasPassed(string $engineName): void
    {
        $engine = $this->engineOrSkip($engineName);

        $this->assertTrue($engine->setIfAbsent('cas-key', 'token-a', 1));
        $this->assertFalse($engine->setIfAbsent('cas-key', 'token-b', 1));

        sleep(2);

        $this->assertTrue($engine->setIfAbsent('cas-key', 'token-b', 30));
        $this->assertEquals('token-b', $engine->get('cas-key'));
    }

    #[DataProvider('engineProvider')]
    public function testSetIfAbsentAppliesTheTtlWithTheWrite(string $engineName): void
    {
        $engine = $this->engineOrSkip($engineName);

        $engine->setIfAbsent('cas-key', 'token-a', 1);
        sleep(2);

        $this->assertNull($engine->get('cas-key'), 'The key must expire on its own, with no second command');
    }

    #[DataProvider('engineProvider')]
    public function testDeleteIfEqualsOnlyRemovesTheMatchingValue(string $engineName): void
    {
        $engine = $this->engineOrSkip($engineName);

        $engine->setIfAbsent('cas-key', 'token-a', 30);

        $this->assertFalse($engine->deleteIfEquals('cas-key', 'token-b'));
        $this->assertEquals('token-a', $engine->get('cas-key'), 'A non-matching caller must not delete');

        $this->assertTrue($engine->deleteIfEquals('cas-key', 'token-a'));
        $this->assertNull($engine->get('cas-key'));
    }

    #[DataProvider('engineProvider')]
    public function testDeleteIfEqualsOnAMissingKeyReturnsFalse(string $engineName): void
    {
        $engine = $this->engineOrSkip($engineName);

        $this->assertFalse($engine->deleteIfEquals('cas-key', 'token-a'));
    }

    /**
     * The scenario the interface exists for: an owner whose TTL quietly lapsed must not be able to
     * delete the key that somebody else has legitimately taken over in the meantime.
     */
    #[DataProvider('engineProvider')]
    public function testAnExpiredOwnerCannotDeleteTheNewOwnersValue(string $engineName): void
    {
        $engine = $this->engineOrSkip($engineName);

        $engine->setIfAbsent('cas-key', 'stale-owner', 1);
        sleep(2);
        $this->assertTrue($engine->setIfAbsent('cas-key', 'new-owner', 30));

        $this->assertFalse($engine->deleteIfEquals('cas-key', 'stale-owner'));
        $this->assertFalse($engine->expireIfEquals('cas-key', 'stale-owner', 30));
        $this->assertEquals('new-owner', $engine->get('cas-key'));
    }

    #[DataProvider('engineProvider')]
    public function testExpireIfEqualsExtendsTheLifetimeOfAMatchingValue(string $engineName): void
    {
        $engine = $this->engineOrSkip($engineName);

        $engine->setIfAbsent('cas-key', 'token-a', 1);
        $this->assertTrue($engine->expireIfEquals('cas-key', 'token-a', 30));

        sleep(2);

        $this->assertEquals('token-a', $engine->get('cas-key'), 'The original 1s expiry must have been replaced');
    }

    #[DataProvider('engineProvider')]
    public function testExpireIfEqualsIsRejectedForANonMatchingValue(string $engineName): void
    {
        $engine = $this->engineOrSkip($engineName);

        $engine->setIfAbsent('cas-key', 'token-a', 1);
        $this->assertFalse($engine->expireIfEquals('cas-key', 'token-b', 30));

        sleep(2);

        $this->assertNull($engine->get('cas-key'), 'The rejected refresh must not have extended anything');
    }

    #[DataProvider('engineProvider')]
    public function testSetIfAbsentRoundTripsNonScalarValues(string $engineName): void
    {
        $engine = $this->engineOrSkip($engineName);

        $this->assertTrue($engine->setIfAbsent('cas-key', ['a' => 1, 'b' => 2], 30));
        $this->assertEquals(['a' => 1, 'b' => 2], $engine->get('cas-key'));

        $this->assertFalse($engine->deleteIfEquals('cas-key', ['a' => 1]));
        $this->assertTrue($engine->deleteIfEquals('cas-key', ['a' => 1, 'b' => 2]));
    }
}
