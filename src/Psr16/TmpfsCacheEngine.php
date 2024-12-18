<?php

namespace ByJG\Cache\Psr16;

use ByJG\Cache\CacheLockInterface;
use ByJG\Cache\Exception\InvalidArgumentException;
use DateInterval;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

class TmpfsCacheEngine extends FileSystemCacheEngine
{

    public function __construct(?LoggerInterface $logger = null)
    {
        parent::__construct('cache', '/dev/shm', $logger);
    }
}
