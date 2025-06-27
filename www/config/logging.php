<?php

use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [
    'default' => 'stack',
    'channels' => [
        'tasks' => [
            'driver' => 'single',
            'path' => jp_data_path('app/tasks.log'),
            'level' => 'debug',
        ],
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
            'ignore_exceptions' => false,
        ],
        'single' => [
            'driver' => 'single',
            'path' => jp_data_path('app/logs/jellyplus.log'),
            'level' => 'debug',
        ],
        'daily' => [
            'driver' => 'daily',
            'path' => jp_data_path('app/logs/jellyplus.log'),
            'level' => 'debug',
            'days' => 90,
        ],
        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => null,
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],
        'syslog' => [
            'driver' => 'syslog',
            'level' => 'debug',
        ],
        'errorlog' => [
            'driver' => 'errorlog',
            'level' => 'debug',
        ],
    ],

];
