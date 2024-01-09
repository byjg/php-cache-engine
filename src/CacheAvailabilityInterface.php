<?php

namespace ByJG\Cache;


interface CacheAvailabilityInterface
{
    /**
     * Return if this CacheEngine is available for use
     * @return bool
     */
    public function isAvailable(): bool;
}
