<?php

namespace Tests;

use ByJG\Cache\Psr16\PhpFileCacheEngine;
use DateTime;

/**
 * PhpFileCacheEngine: the PSR-16 tests of every engine (CachePSR16Test), and what is particular to it
 */
class PhpFileCacheEngineTest extends CachePSR16Test
{
    public static function CachePoolProvider(): array
    {
        return [
            'PhpFile' => [new PhpFileCacheEngine('test-phpfile')],
        ];
    }

    private function engine(): PhpFileCacheEngine
    {
        return $this->cacheEngine = new PhpFileCacheEngine('test-phpfile');
    }

    private function file(string $key): string
    {
        return sys_get_temp_dir() . "/test-phpfile-$key.php";
    }

    /**
     * Scalars and arrays are written as PHP code (OPcache keeps them in memory), the rest serialized: both come back
     * as they were
     */
    public function testAValueComesBackAsItWas(): void
    {
        $engine = $this->engine();
        $values = [
            'string' => "it's a \"string\"\n\\ with \0 and ü",
            'int' => PHP_INT_MAX,
            'float' => 0.1 + 0.2,
            'bool' => false,
            'array' => ['a' => [1, 2.5, 'x', true, null], 7 => 'seven'],
            'object' => new DateTime('2024-01-02 03:04:05'),
            'infinite' => INF,
        ];
        foreach ($values as $key => $value) {
            $engine->set($key, $value);
        }

        foreach ($values as $key => $value) {
            $this->assertEquals($value, $engine->get($key), $key);
        }
        $this->assertSame($values['float'], $engine->get('float'));
        $this->assertStringNotContainsString('serialize', file_get_contents($this->file('array')));
        $this->assertStringContainsString('O:8:\"DateTime\"', addslashes(file_get_contents($this->file('object'))));
    }

    /**
     * An entry can hold a secret (a cached configuration): only its owner can read the file
     */
    public function testTheFileIsReadableByItsOwnerOnly(): void
    {
        $this->engine()->set('secret', 'password');

        $this->assertSame(0600, fileperms($this->file('secret')) & 0777);
    }

    public function testAValueReplacedIsReadAsItIsNow(): void
    {
        $engine = $this->engine();
        $engine->set('key', 'first');
        $this->assertSame('first', $engine->get('key'));

        $engine->set('key', 'second');

        $this->assertSame('second', $engine->get('key'));
        // No temporary file is left behind
        $this->assertSame([$this->file('key')], glob(sys_get_temp_dir() . '/test-phpfile-key.php*'));
    }

    public function testAFileThatIsNotAnEntryIsNotFound(): void
    {
        file_put_contents($this->file('other'), "<?php\nreturn 'something else';\n");

        $this->assertFalse($this->engine()->has('other'));
        $this->assertSame('default', $this->engine()->get('other', 'default'));
    }

    public function testAnEngineInADirectoryThatDoesNotExistIsNotAvailable(): void
    {
        $engine = new PhpFileCacheEngine('test-phpfile', sys_get_temp_dir() . '/does-not-exist-' . uniqid());

        $this->assertFalse($engine->isAvailable());
        $this->assertFalse($engine->set('key', 'value'));
    }
}
