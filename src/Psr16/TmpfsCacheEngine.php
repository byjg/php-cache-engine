<?php

namespace ByJG\Cache\Psr16;

use Psr\Log\LoggerInterface;

class TmpfsCacheEngine extends FileSystemCacheEngine
{

    public function __construct(string $prefix = "cache", ?LoggerInterface $logger = null)
    {
        parent::__construct($prefix, '/dev/shm', $logger);
    }
}
