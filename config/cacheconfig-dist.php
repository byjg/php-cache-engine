<?php

return [
    'default' => [
        'instance' => '\\ByJG\\Cache\\NoCacheEngine',
        'memcached' => [
            'servers' => [
                '127.0.0.1:11211'
            ]
        ],
        'shmop' => [
            'max-size' => 1048576,
            'default-permission' => '0700'
        ]
    ]
];
