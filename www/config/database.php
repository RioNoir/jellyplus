<?php

/**
 * Database Configuration
 */

return [

    'default' => env('JP_DB_CONNECTION', 'default'),
    'connections' => [

        'default' => [
            'driver' => 'sqlite',
            'database' => jp_data_path('/app/database.sqlite'),
            'prefix' => '',
        ],

        'jellyfin' => [
            'driver' => 'sqlite',
            'database' => jp_data_path('/jellyfin/data/jellyfin.db'),
            'prefix' => '',
        ],

        'library' => [
            'driver' => 'sqlite',
            'database' => jp_data_path('/jellyfin/data/library.db'),
            'prefix' => '',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => env('JP_DB_HOST', '127.0.0.1'),
            'port' => env('JP_DB_PORT', 3306),
            'database' => env('JP_DB_DATABASE', 'forge'),
            'username' => env('JP_DB_USERNAME', 'forge'),
            'password' => env('JP_DB_PASSWORD', ''),
            'unix_socket' => env('JP_DB_SOCKET', ''),
            'charset' => env('JP_DB_CHARSET', 'utf8mb4'),
            'collation' => env('JP_DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => env('JP_DB_PREFIX', ''),
            'strict' => env('JP_DB_STRICT_MODE', false),
            'engine' => env('JP_DB_ENGINE'),
            'timezone' => env('JP_DB_TIMEZONE', '+00:00'),
        ],
    ],
    'migrations' => 'migrations',
];
