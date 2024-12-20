<?php

namespace ByJG\Cache;

interface GarbageCollectorInterface
{
    public function collectGarbage();

    public function getTtl(string $key): ?int;
}