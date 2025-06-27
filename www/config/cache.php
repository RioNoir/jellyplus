<?php

/**
 * Cache Configuration
 */

return [

    'default' => env('JP_CACHE_DRIVER', 'file'),
    'stores' => [

        'apc' => [
            'driver' => 'apc',
        ],

        'array' => [
            'driver' => 'array',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => 'jellyplus',
        ],

        'file' => [
            'driver' => 'file',
            'path' => jp_data_path('app/cache'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('JP_APP_MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('JP_APP_MEMCACHED_USERNAME'),
                env('JP_APP_MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('JP_APP_MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('JP_APP_MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('JP_APP_CACHE_REDIS_CONNECTION', 'cache'),
        ],

    ],
    'prefix' => 'jellyplus_cache',
];
