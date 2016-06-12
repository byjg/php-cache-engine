<?php

return [

    /**
     * Defines the cache instance.
     * 'default' is the basic usage 
     */
    'default' => [
        'instance' => '\\ByJG\\Cache\\NoCacheEngine',
    ],

    /**
     * Create a cache instance of MemcachedEngine and set some parameters to memcache:
     */
    'memcache-sample' => [
        'instance' => '\\ByJG\\Cache\\MemcachedEngine',
        'memcached' => [
            'servers' => [
                '127.0.0.1:11211'
            ]
        ]
    ],

    /**
     * Create a cache instance of MemcachedEngine and set some parameters to memcache:
     */
    'shmop-sample' => [
        'instance' => '\\ByJG\\Cache\\ShmopCacheEngine',
        'shmop' => [
            'max-size' => 1048576,
            'default-permission' => '0700'
        ]
    ]
];
