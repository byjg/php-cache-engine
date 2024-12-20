<?php

namespace ByJG\Cache\Psr16;

use Psr\Log\LoggerInterface;

class TmpfsCacheEngine extends FileSystemCacheEngine
{

    public function __construct(?LoggerInterface $logger = null)
    {
        parent::__construct('cache', '/dev/shm', $logger);
    }
}
