<?php

namespace Tests;

use ByJG\Cache\AtomicOperationInterface;
use ByJG\Cache\Exception\InvalidArgumentException;
use ByJG\Cache\GarbageCollectorInterface;
use ByJG\Cache\Psr16\BaseCacheEngine;
use ByJG\Cache\Psr16\MemcachedEngine;
use ByJG\Cache\Psr16\NoCacheEngine;
use ByJG\Cache\Psr16\RedisCacheEngine;
use ByJG\Cache\Psr16\SessionCacheEngine;
use PHPUnit\Framework\Attributes\DataProvider;

class CachePSR16Test extends TestBase
{
    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testGetOneItem(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable()) {
            // First time
            $item = $cacheEngine->get('chave', null);
            $this->assertNull($item);
            $item = $cacheEngine->get('chave', 'default');
            $this->assertEquals('default', $item);

            // Set object
            $cacheEngine->set('chave', 'valor');

            // Get Object
            if (!($cacheEngine instanceof NoCacheEngine)) {
                $item2 = $cacheEngine->get('chave', 'default');
                $this->assertEquals('valor', $item2);
            }

            // Remove
            $cacheEngine->delete('chave');

            // Check Removed
            $item = $cacheEngine->get('chave');
            $this->assertNull($item);
        } else {
            $this->markTestSkipped('Object is not fully functional');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testGetMultipleItems(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable()) {
            // First time
            $items = [...$cacheEngine->getMultiple(['chave1', 'chave2'])];
            $this->assertNull($items['chave1']);
            $this->assertNull($items['chave2']);
            $items = [...$cacheEngine->getMultiple(['chave1', 'chave2'], 'default')];
            $this->assertEquals('default', $items['chave1']);
            $this->assertEquals('default', $items['chave2']);

            // Set object
            $cacheEngine->set('chave1', 'valor1');
            $cacheEngine->set('chave2', 'valor2');

            // Get Object
            if (!($cacheEngine instanceof NoCacheEngine)) {
                $item2 = [...$cacheEngine->getMultiple(['chave1', 'chave2'])];
                $this->assertEquals('valor1', $item2['chave1']);
                $this->assertEquals('valor2', $item2['chave2']);
            }

            // Remove
            $cacheEngine->deleteMultiple(['chave1', 'chave2']);

            // Check Removed
            $items = [...$cacheEngine->getMultiple(['chave1', 'chave2'])];
            $this->assertNull($items['chave1']);
            $this->assertNull($items['chave2']);
        } else {
            $this->markTestSkipped('Object is not fully functional');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testTtl(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable()) {
            // First time
            $item = $cacheEngine->get('chave');
            $this->assertNull($item);
            $this->assertFalse($cacheEngine->has('chave'));
            $item2 = $cacheEngine->get('chave2');
            $this->assertNull($item2);
            $this->assertFalse($cacheEngine->has('chave2'));

            // Set object
            $cacheEngine->set('chave', 'valor', 2);
            $cacheEngine->set('chave2', 'valor2', 2);

            // Get Object
            if (!($cacheEngine instanceof NoCacheEngine)) {
                $item2 = $cacheEngine->get('chave');
                $this->assertEquals('valor', $item2);
                $this->assertTrue($cacheEngine->has('chave2'));
                sleep(3);
                $item2 = $cacheEngine->get('chave');
                $this->assertNull($item2);
                $this->assertFalse($cacheEngine->has('chave2'));
            }
        } else {
            $this->markTestSkipped('Object is not fully functional');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testCacheObject(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable()) {
            // First time
            $item = $cacheEngine->get('chave');
            $this->assertNull($item);

            // Set object
            $model = new Model(10, 20);
            $cacheEngine->set('chave', $model);

            // Get Object
            if (!($cacheEngine instanceof NoCacheEngine)) {
                $item2 = $cacheEngine->get('chave');
                $this->assertEquals($model, $item2);
            }

            // Delete
            $cacheEngine->delete('chave');
            $item = $cacheEngine->get('chave');
            $this->assertNull($item);
        } else {
            $this->markTestSkipped('Object is not fully functional');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testCacheArray(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable()) {
            // First time
            $item = $cacheEngine->get('chave');
            $this->assertNull($item);

            // Get Object
            if (($cacheEngine instanceof NoCacheEngine)) {
                return;
            }

            // Set object
            $cacheEngine->set('chave', [ 'a' => 10, 'b' => 20 ]);
            $this->assertEquals([ 'a' => 10, 'b' => 20 ], $cacheEngine->get('chave'));

            $cacheEngine->set('chave', [ 10, 20 ]);
            $this->assertEquals([ 10, 20 ], $cacheEngine->get('chave'));

            $cacheEngine->set('chave', [ 'a' => 10, 'b' => new Model(1, 2) ]);
            $this->assertEquals([ 'a' => 10, 'b' => new Model(1, 2) ], $cacheEngine->get('chave'));

            // Delete
            $cacheEngine->delete('chave');
            $item = $cacheEngine->get('chave');
            $this->assertNull($item);
        } else {
            $this->markTestSkipped('Object is not fully functional');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testClear(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable()) {
            // Values
            $empty = [
                'chave'  => null,
                'chave2' => null,
                'chave3' => null
            ];
            $set = [
                'chave'  => 'val',
                'chave2' => 'val2',
                'chave3' => 'val3'
            ];

            // First time
            $item = $cacheEngine->getMultiple(['chave', 'chave2', 'chave3']);
            $this->assertEquals($empty, $item);

            // Set and Check
            $cacheEngine->setMultiple($set);
            if (!($cacheEngine instanceof NoCacheEngine)) {
                $item = $cacheEngine->getMultiple(['chave', 'chave2', 'chave3']);
                $this->assertEquals($set, $item);
            }

            // Clear and Check
            $cacheEngine->clear();
            $item = $cacheEngine->getMultiple(['chave', 'chave2', 'chave3']);
            $this->assertEquals($empty, $item);
        } else {
            $this->markTestSkipped('Object is not fully functional');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testCacheContainerKeyNonExistent(BaseCacheEngine $cacheEngine)
    {
        if ($cacheEngine->isAvailable()) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage("Key 'abc' not found in container");

            $cacheEngine->withKeysFromContainer(new BasicContainer());
            $cacheEngine->set("abc", "30");
        } else {
            $this->markTestSkipped('Object is not fully functional');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testCacheContainerKey(BaseCacheEngine $cacheEngine)
    {
        if ($cacheEngine->isAvailable() && !($cacheEngine instanceof NoCacheEngine)) {
            $cacheEngine->clear();

            // This first part will get the "test-key" from the container.
            // The real key is container["test-key"] = "container-key"
            $cacheEngine->withKeysFromContainer(new BasicContainer());
            $this->assertFalse($cacheEngine->has("test-key"));
            $cacheEngine->set("test-key", "something");
            $this->assertTrue($cacheEngine->has("test-key"));
            $this->assertEquals("something", $cacheEngine->get("test-key"));

            // This part, we will disable the container and try to get the original key from test.
            $cacheEngine->withKeysFromContainer(null);
            $this->assertTrue($cacheEngine->has("container-key"));
            $this->assertEquals("something", $cacheEngine->get("container-key"));
        } else {
            $this->markTestSkipped('Object is not fully functional');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testGarbageCollector(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable() && ($cacheEngine instanceof GarbageCollectorInterface)) {
            // First time
            $cacheEngine->set('chave', "ok");
            $this->assertTrue($cacheEngine->has('chave'));
            $this->assertNull($cacheEngine->getTtl('chave'));
            $cacheEngine->delete('chave');
            $this->assertFalse($cacheEngine->has('chave'));

            // Set TTL
            $cacheEngine->set('chave', "ok", 1);
            $this->assertTrue($cacheEngine->has('chave'));
            $this->assertNotNull($cacheEngine->getTtl('chave'));
            $cacheEngine->collectGarbage();
            $this->assertTrue($cacheEngine->has('chave'));
            $this->assertNotNull($cacheEngine->getTtl('chave')); // Should not delete yet
            sleep(1);
            $cacheEngine->collectGarbage();
            $this->assertNull($cacheEngine->getTtl('chave')); // Should be deleted
            $this->assertFalse($cacheEngine->has('chave'));
        } else {
            $this->markTestSkipped('Does not support garbage collector or it is native');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testAtomicIncrement(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable() && ($cacheEngine instanceof AtomicOperationInterface)) {
            $cacheEngine->set('chave', 10);
            $this->assertEquals(11, $cacheEngine->increment('chave'));
            $this->assertEquals(12, $cacheEngine->increment('chave'));
            $this->assertEquals(13, $cacheEngine->increment('chave'));
            $this->assertEquals(14, $cacheEngine->increment('chave'));
            $this->assertEquals(15, $cacheEngine->increment('chave'));
            $this->assertEquals(15, $cacheEngine->get('chave'));
        } else {
            $this->markTestSkipped('Does not support atomic increment or it is native');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testAtomicDecrement(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable() && ($cacheEngine instanceof AtomicOperationInterface)) {
            $cacheEngine->set('chave', 10);
            $this->assertEquals(9, $cacheEngine->decrement('chave'));
            $this->assertEquals(8, $cacheEngine->decrement('chave'));
            $this->assertEquals(7, $cacheEngine->decrement('chave'));
            $this->assertEquals(6, $cacheEngine->decrement('chave'));
            $this->assertEquals(5, $cacheEngine->decrement('chave'));
            $this->assertEquals(5, $cacheEngine->get('chave'));
        } else {
            $this->markTestSkipped('Does not support atomic decrement or it is native');
        }
    }

    /**
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testAtomicAdd(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable() && ($cacheEngine instanceof AtomicOperationInterface)) {
            $this->assertEquals([10], $cacheEngine->add('chave', 10));
            $this->assertEquals([10, 20], $cacheEngine->add('chave', 20));
            $this->assertEquals([10, 20, "value"], $cacheEngine->add('chave', "value"));
            $this->assertEquals([10, 20, "value"], $cacheEngine->get('chave'));

            $cacheEngine->set('chave', 10);
            $this->assertEquals(10, $cacheEngine->get('chave'));
            $this->assertEquals([10, 20], $cacheEngine->add('chave', 20));

            $cacheEngine->set('chave', ["A", "B"]);
            $this->assertEquals(["A", "B"], $cacheEngine->get('chave'));
            $this->assertEquals(["A", "B", "C"], $cacheEngine->add('chave', "C"));

            $cacheEngine->set('chave', new Model(10, 20));
            $this->assertEquals(new Model(10, 20), $cacheEngine->get('chave'));
            $this->assertEquals([new Model(10, 20), new Model(20, 30)], $cacheEngine->add('chave', new Model(20, 30)));

            $cacheEngine->set('chave', [new Model(10, 20)]);
            $this->assertEquals([new Model(10, 20)], $cacheEngine->get('chave'));
            $this->assertEquals([new Model(10, 20), new Model(20, 30)], $cacheEngine->add('chave', new Model(20, 30)));


        } else {
            $this->markTestSkipped('Does not support atomic add or it is native');
        }
    }

    /**
     * Every public method has to establish the connection on its own. RedisCacheEngine::has() and
     * clear() read $this->redis without calling lazyLoadRedisServer() first, so either one used as
     * the very first operation on a new instance died with "call to a member function on null".
     *
     * Each assertion needs an instance that has never been touched, so the engines are constructed
     * here rather than taken from the shared data provider.
     */
    public function testEveryEntryPointConnectsOnItsOwn()
    {
        if (!(new RedisCacheEngine())->isAvailable()) {
            $this->markTestSkipped('Redis is not available');
        }

        $this->assertFalse((new RedisCacheEngine())->has('never-touched-key'));
        $this->assertTrue((new RedisCacheEngine())->clear());

        $this->assertFalse((new MemcachedEngine())->has('never-touched-key'));
        $this->assertTrue((new MemcachedEngine())->clear());
    }

    /**
     * The TTL argument of the atomic operations has to mean the same thing it means everywhere
     * else: seconds from now. FileSystem used to hand it straight to the expiry file, which stores
     * an absolute timestamp, so a value written with a TTL of 60 expired in January 1970 and the
     * key was already unreadable by the time the caller looked at it.
     *
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testAtomicOperationsHonourTheTtl(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable() && ($cacheEngine instanceof AtomicOperationInterface)) {
            $this->assertEquals(1, $cacheEngine->increment('ttl-counter', 1, 30));
            $this->assertEquals(2, $cacheEngine->increment('ttl-counter', 1, 30));
            $this->assertEquals(2, $cacheEngine->get('ttl-counter'), 'A 30s TTL must still be alive');

            $this->assertEquals(['x'], $cacheEngine->add('ttl-list', 'x', 30));
            $this->assertEquals(['x'], $cacheEngine->get('ttl-list'), 'A 30s TTL must still be alive');

            $cacheEngine->increment('expiring-counter', 1, 1);
            $cacheEngine->add('expiring-list', 'x', 1);
            sleep(2);
            $this->assertNull($cacheEngine->get('expiring-counter'), 'A 1s TTL must have elapsed');
            $this->assertNull($cacheEngine->get('expiring-list'), 'A 1s TTL must have elapsed');
        } else {
            $this->markTestSkipped('Does not support atomic operations or it is native');
        }
    }

    /**
     * A TTL handed to increment()/decrement() applies to the key whether or not the call created
     * it. Memcached only honoured it on creation, so a counter that already existed kept whatever
     * expiry it had - none at all, here - and outlived the TTL the caller had just asked for.
     *
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testAtomicOperationsApplyTheTtlToAnExistingKey(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable() && ($cacheEngine instanceof AtomicOperationInterface)) {
            $this->assertEquals(1, $cacheEngine->increment('existing-up'));
            $cacheEngine->set('existing-down', 10);

            $this->assertEquals(2, $cacheEngine->increment('existing-up', 1, 1));
            $this->assertEquals(9, $cacheEngine->decrement('existing-down', 1, 1));
            sleep(2);
            $this->assertNull($cacheEngine->get('existing-up'), 'A 1s TTL must have elapsed');
            $this->assertNull($cacheEngine->get('existing-down'), 'A 1s TTL must have elapsed');
        } else {
            $this->markTestSkipped('Does not support atomic operations or it is native');
        }
    }

    /**
     * clear() and isAvailable() are about the whole engine, not about one key, so they must not
     * resolve anything through the PSR-11 container. They used to: Redis deleted every scanned key
     * through delete(), and FileSystem built its glob pattern and its probe path through fixKey(),
     * so with a container set they died with "Key '...' not found in container".
     *
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testClearWithKeysFromContainer(BaseCacheEngine $cacheEngine)
    {
        if ($cacheEngine->isAvailable()) {
            $cacheEngine->withKeysFromContainer(new BasicContainer());
            $cacheEngine->set("test-key", "something");

            $this->assertTrue($cacheEngine->isAvailable());
            $this->assertTrue($cacheEngine->clear());
            $this->assertFalse($cacheEngine->has("test-key"));
        } else {
            $this->markTestSkipped('Object is not fully functional');
        }
    }

    /**
     * Same reasoning as clear(): collecting garbage walks every entry of the engine.
     *
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testGarbageCollectorWithKeysFromContainer(BaseCacheEngine $cacheEngine)
    {
        if ($cacheEngine->isAvailable() && ($cacheEngine instanceof GarbageCollectorInterface)) {
            $cacheEngine->withKeysFromContainer(new BasicContainer());
            $cacheEngine->set("test-key", "something", 1);
            $this->assertNotNull($cacheEngine->getTtl("test-key"));

            sleep(2);
            $cacheEngine->collectGarbage();
            $this->assertNull($cacheEngine->getTtl("test-key"));
        } else {
            $this->markTestSkipped('Does not support garbage collector or it is native');
        }
    }

    /**
     * Leaving the TTL out must not change the expiry a key already has. FileSystem used to drop
     * it, so a counter created with a TTL became permanent on the first call that omitted it.
     *
     * Memcached's add() is the one exception and is left out on purpose: it appends through cas(),
     * which has to send an expiration, and Memcached offers no way to read the time a key has left.
     *
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testAtomicOperationsKeepTheExpiryWhenTheTtlIsOmitted(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable() && ($cacheEngine instanceof AtomicOperationInterface)) {
            $cacheEngine->increment('kept-up', 1, 1);
            $cacheEngine->increment('kept-down', 5, 1);
            $cacheEngine->add('kept-list', 'x', 1);

            $this->assertEquals(2, $cacheEngine->increment('kept-up'));
            $this->assertEquals(4, $cacheEngine->decrement('kept-down'));
            $this->assertEquals(['x', 'y'], $cacheEngine->add('kept-list', 'y'));
            sleep(2);
            $this->assertNull($cacheEngine->get('kept-up'), 'The 1s TTL must still apply');
            $this->assertNull($cacheEngine->get('kept-down'), 'The 1s TTL must still apply');
            if (!($cacheEngine instanceof MemcachedEngine)) {
                $this->assertNull($cacheEngine->get('kept-list'), 'The 1s TTL must still apply');
            }
        } else {
            $this->markTestSkipped('Does not support atomic operations or it is native');
        }
    }

    /**
     * An expired key is an absent key. FileSystem only notices the expiry when the key is read,
     * so an atomic operation arriving first carried on from the value that should be gone.
     *
     * @param BaseCacheEngine $cacheEngine
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    #[DataProvider('CachePoolProvider')]
    public function testAtomicOperationsStartOverOnAnExpiredKey(BaseCacheEngine $cacheEngine)
    {
        $this->cacheEngine = $cacheEngine;

        if ($cacheEngine->isAvailable() && ($cacheEngine instanceof AtomicOperationInterface)) {
            $cacheEngine->increment('expired-counter', 7, 1);
            $cacheEngine->increment('expired-counter-no-ttl', 7, 1);
            $cacheEngine->add('expired-list', 'x', 1);
            sleep(2);

            $this->assertEquals(1, $cacheEngine->increment('expired-counter', 1, 30));
            $this->assertEquals(1, $cacheEngine->increment('expired-counter-no-ttl'));
            $this->assertEquals(['y'], $cacheEngine->add('expired-list', 'y', 30));
            $this->assertEquals(1, $cacheEngine->get('expired-counter-no-ttl'), 'The new counter has no expiry');
        } else {
            $this->markTestSkipped('Does not support atomic operations or it is native');
        }
    }

    /**
     * The session belongs to the application, not to the cache. clear() used to call
     * session_destroy(), which took the login and everything else stored there along with it.
     */
    public function testSessionClearOnlyRemovesItsOwnEntries()
    {
        $cache = new SessionCacheEngine('mine');
        $other = new SessionCacheEngine('theirs');

        $cache->set('key', 'value', 30);
        $other->set('key', 'other value');
        $_SESSION['user'] = 'logged in';

        $this->assertTrue($cache->clear());

        $this->assertFalse($cache->has('key'));
        $this->assertSame([], array_filter(array_keys($_SESSION), fn($name) => str_starts_with($name, 'mine-')));
        $this->assertEquals('other value', $other->get('key'));
        $this->assertEquals('logged in', $_SESSION['user']);

        $other->clear();
        unset($_SESSION['user']);
    }

    /**
     * The logger argument was untyped and anything that was not a LoggerInterface was silently
     * replaced by a NullLogger, hiding the mistake. It is now typed like every other engine.
     */
    public function testMemcachedRejectsALoggerOfTheWrongType()
    {
        $this->expectException(\TypeError::class);
        /** @psalm-suppress InvalidArgument */
        new MemcachedEngine(null, 'not-a-logger');
    }
}
