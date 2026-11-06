<?php

namespace ByJG\Cache\Psr16;

use DateInterval;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * A cache whose entries are PHP files (`<?php return [...];`), read with `include`: with OPcache, an entry is read
 * from memory, not from the disk. A value of scalars and arrays is written as PHP code, kept in OPcache's shared
 * memory with nothing to decode; any other value is written serialized.
 *
 * For entries written once and read on every request (a DI container, compiled data). Each write compiles the
 * file again, and OPcache keeps the previous version as wasted memory until it restarts: do not use it for values
 * that change often (sessions, query results, counters).
 *
 * The files are readable by their owner only (0600), as an entry may hold secrets (a cached configuration).
 */
class PhpFileCacheEngine extends BaseCacheEngine
{
    protected LoggerInterface $logger;

    protected string $prefix;

    protected string $path;

    /**
     * @param string $prefix The start of the name of the files
     * @param string|null $path The directory of the files; the system temp dir by default
     * @param LoggerInterface|null $logger
     */
    public function __construct(string $prefix = 'cache', ?string $path = null, ?LoggerInterface $logger = null)
    {
        $this->prefix = $prefix;
        $this->path = $path ?? sys_get_temp_dir();
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    #[Override]
    public function get(string $key, mixed $default = null): mixed
    {
        $entry = $this->read($this->fileName($this->getKeyFromContainer($key)));
        if ($entry === null) {
            $this->logger->info("[PhpFile cache] Not found '$key'");
            return $default;
        }

        $this->logger->info("[PhpFile cache] Get '$key'");
        [, $serialized, $value] = $entry;
        return $serialized ? unserialize($value) : $value;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    #[Override]
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $file = $this->fileName($this->getKeyFromContainer($key));

        // As FileSystemCacheEngine: a null value removes the entry
        if (is_null($value)) {
            $this->remove($file);
            return true;
        }

        $this->logger->info("[PhpFile cache] Set '$key'");
        $serialized = !self::isExportable($value);
        $entry = [$this->addToNow($ttl), $serialized, $serialized ? serialize($value) : $value];
        $code = "<?php\n\nreturn " . var_export($entry, true) . ";\n";

        // Written to a temporary file and renamed: a request never includes a file half written
        $temp = $file . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (@file_put_contents($temp, $code) === false) {
            $this->logger->warning("[PhpFile cache] Could not write '$key' in '$this->path'");
            return false;
        }
        chmod($temp, 0600);
        if (!rename($temp, $file)) {
            @unlink($temp);
            $this->logger->warning("[PhpFile cache] Could not write '$key' in '$this->path'");
            return false;
        }
        $this->invalidate($file);

        return true;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    #[Override]
    public function delete(string $key): bool
    {
        $this->remove($this->fileName($this->getKeyFromContainer($key)));
        return true;
    }

    #[Override]
    public function clear(): bool
    {
        foreach (glob($this->fileName('*')) ?: [] as $file) {
            $this->remove($file);
        }
        return true;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    #[Override]
    public function has(string $key): bool
    {
        return $this->read($this->fileName($this->getKeyFromContainer($key))) !== null;
    }

    #[Override]
    public function isAvailable(): bool
    {
        return is_dir($this->path) && is_writable($this->path);
    }

    /**
     * The file of a key (or of a glob pattern)
     */
    protected function fileName(string $key): string
    {
        return $this->path . '/' . $this->prefix . '-' . preg_replace("/[\/\\\\]/", "#", $key) . '.php';
    }

    /**
     * The entry of a file: [expires at, serialized, value]; null when there is none or it expired (then removed)
     *
     * @return array{0: int|null, 1: bool, 2: mixed}|null
     */
    protected function read(string $file): ?array
    {
        if (!is_file($file)) {
            return null;
        }
        $entry = include $file;
        if (!is_array($entry) || count($entry) !== 3) {
            return null;
        }
        if ($entry[0] !== null && time() >= $entry[0]) {
            $this->remove($file);
            return null;
        }
        return $entry;
    }

    protected function remove(string $file): void
    {
        if (is_file($file)) {
            unlink($file);
            $this->invalidate($file);
        }
    }

    /**
     * OPcache must not serve the previous version of a file that changed (with opcache.validate_timestamps off,
     * it would never see the change)
     */
    protected function invalidate(string $file): void
    {
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($file, true);
        }
    }

    /**
     * Whether var_export() writes the value as PHP code that gives it back as it is: scalars and arrays of them
     */
    protected static function isExportable(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (!self::isExportable($item)) {
                    return false;
                }
            }
            return true;
        }
        // A float that var_export() cannot write back exactly (INF, NAN) is serialized
        return is_null($value) || is_bool($value) || is_int($value) || is_string($value)
            || (is_float($value) && is_finite($value));
    }
}
