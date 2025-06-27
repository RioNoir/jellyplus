<?php

return [
    'default' => 'database',
    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],
        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => (60 * 10),
        ],
    ],
    'failed' => [
        'driver' => 'database-uuids',
        'database' => 'default',
        'table' => 'failed_jobs',
    ],
];
