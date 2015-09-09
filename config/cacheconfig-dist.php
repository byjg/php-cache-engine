<?php
return [
    'default' => [
        'instance' => '\\ByJG\\Cache\\NoCacheEngine',
        'memcached' => [
            'servers' => [
                '127.0.0.1:11211'
            ]
        ]
    ]
];
