<?php

return [
    'cache_ttl' => 30, //Minutes
    'resolution' => env('STREAM_QUALITY', '1080p'),
    'format' => env('STREAM_FORMAT', 'bluray'),
    'lang' => env('STREAM_LANG', 'eng'),
    'resolutions' => [
        '8k',
        '4k',
        '2160p',
        '1440p',
        '1080p',
        '720p',
        '576p',
        '480p',
        '360p',
        '240p',
        'n/a'
    ],
    'formats' => [
        'bluray',
        'brrip',
        'bdrip',
        'uhdrip',
        'webmux',
        'web-dl',
        'webdl',
        'web-dlrip',
        'webrip',
        'hdrip',
        'dvd',
        'dvdrip',
        'hdtv',
        'satrip',
        'tvrip',
        'ppvrip',
        'cam',
        'telesync',
        'telecine',
        'scr',
        'none',
        'n/a'
    ],
    'sortby_keywords' => [],
    'included_keywords' => [],
    'excluded_keywords' => [],
    'excluded_formats' => [
        '3d',
        'none',
        'n/a'
    ],
    'excluded_paths' => [
        '/static/exceptions/transfer_error.mp4',
        '/static/exceptions/torrent_not_downloaded.mp4',
        '/static/exceptions/no_matching_file.mp4',
        '/static/exceptions/filtered_no_streams.mp4'
    ]
];
