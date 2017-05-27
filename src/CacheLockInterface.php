<?php
/**
 * User: jg
 * Date: 26/05/17
 * Time: 01:38
 */

namespace ByJG\Cache;


interface CacheLockInterface
{
    /**
     * Lock resource before set it.
     * @param string $key
     */
    public function lock($key);

    /**
     * Unlock resource
     * @param string $key
     */
    public function unlock($key);
}
