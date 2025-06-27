<?php


use Carbon\Carbon;

return [
    "list" => [
        md5('cache:clear') => [
            'Name' => 'Clears the cache of the whole app (searches, streams and addons)',
            'Category' => jp_config('app.name'),
            'Description' => '',
            'Id' => md5('cache:clear'),
            'IsHidden' => true,
            'Key' => 'cache:clear',
            'LastExecutionResult' => [
                'EndTimeUtc' => task_end('cache:clear'),
                'Id' => md5('cache:clear'),
                'Key' => 'cache:flush',
                'StartTimeUtc' => task_start('cache:clear'),
                'Status' => task_status('cache:clear'),
            ],
            'State' => 'Idle',
            'Triggers' => [],
        ],
        md5('library:update') => [
            'Name' => 'Updates TV series episodes of the Library',
            'Category' => jp_config('app.name'),
            'Description' => '',
            'Id' => md5('library:update'),
            'IsHidden' => true,
            'Key' => 'library:update',
            'LastExecutionResult' => [
                'EndTimeUtc' => task_end('library:update'),
                'Id' => md5('library:update'),
                'Key' => 'library:update',
                'StartTimeUtc' => task_start('library:update'),
                'Status' => task_status('library:update'),
            ],
            'State' => 'Idle',
            'Triggers' => [],
        ],
        md5('library:clean') => [
            'Name' => 'Cleans library, deletes old streams',
            'Category' => jp_config('app.name'),
            'Description' => '',
            'Id' => md5('library:clean'),
            'IsHidden' => true,
            'Key' => 'library:clean',
            'LastExecutionResult' => [
                'EndTimeUtc' => task_end('library:clean'),
                'Id' => md5('library:clean'),
                'Key' => 'library:clean',
                'StartTimeUtc' => task_start('library:clean'),
                'Status' => task_status('library:clean'),
            ],
            'State' => 'Idle',
            'Triggers' => [],
        ],
        md5('library:rebuild') => [
            'Name' => 'Rebuild library, recreates .nfo and .strm files',
            'Category' => jp_config('app.name'),
            'Description' => '',
            'Id' => md5('library:rebuild'),
            'IsHidden' => true,
            'Key' => 'library:rebuild',
            'LastExecutionResult' => [
                'EndTimeUtc' => task_end('library:rebuild'),
                'Id' => md5('library:rebuild'),
                'Key' => 'library:rebuild',
                'StartTimeUtc' => task_start('library:rebuild'),
                'Status' => task_status('library:rebuild'),
            ],
            'State' => 'Idle',
            'Triggers' => [],
        ],
        md5('library:playback-info') => [
            'Name' => 'Gets the Playback Info of the items in the library',
            'Category' => jp_config('app.name'),
            'Description' => '',
            'Id' => md5('library:playback-info'),
            'IsHidden' => true,
            'Key' => 'library:playback-info',
            'LastExecutionResult' => [
                'EndTimeUtc' => task_end('library:playback-info'),
                'Id' => md5('library:playback-info'),
                'Key' => 'library:playback-info',
                'StartTimeUtc' => task_start('library:playback-info'),
                'Status' => task_status('library:playback-info'),
            ],
            'State' => 'Idle',
            'Triggers' => [],
        ],
//        md5('library:clear') => [
//            'Name' => '⚠️ Completely deletes the library and restores the initial configuration ⚠️',
//            'Category' => jp_config('app.name'),
//            'Description' => '',
//            'Id' => md5('library:clear'),
//            'IsHidden' => true,
//            'Key' => 'library:clear',
//            'LastExecutionResult' => [
//                'EndTimeUtc' => task_end('library:clear'),
//                'Id' => md5('library:clear'),
//                'Key' => 'library:update',
//                'StartTimeUtc' => task_start('library:clear'),
//                'Status' => task_status('library:clear'),
//            ],
//            'State' => 'Idle',
//            'Triggers' => [],
//        ],
    ],
    'cron' => [
        md5('library:clean') => '0 */6 * * *',
        md5('library:update') => '0 */12 * * *',
        md5('library:rebuild') => '0 */4 * * *',
        //md5('library:playback-info') => '0 */4 * * *'
        md5('library:playback-info') => '',
        md5('delete:images') => '0 0 * * 0'
    ],
];
