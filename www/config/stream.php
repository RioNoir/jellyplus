<?php

return [
    'cache_ttl' => 30, //Minutes
    'stream_url_ttl' => 20, //Minutes
    'stream_expires_ttl' => 1440, //Minutes
    'resolution' => env('STREAM_QUALITY', 'fullhd_1080p'),
    'format' => env('STREAM_FORMAT', 'bluray'),
    'compression' => env('STREAM_COMPRESSION', 'H264_MPEG-4_AVC'),
    'quality' => env('STREAM_QUALITY', 'SDR'),
    'audio_format' => env('STREAM_AUDIO', 'AAC'),
    'lang' => env('STREAM_LANG', 'eng'),
    'resolutions' => [
        '8k_4320p',
        '4k_2160p',
        '2k_1440p',
        'fullhd_1080p',
        'hd_720p',
        '576p',
        '480p',
        '360p',
        '240p',
        'n/a'
    ],
    'formats' => [
        'bluray',
        'uhdrip',
        'bdrip',
        'brrip',
        'web-dl_webdl',
        'webmux',
        'web-dlrip',
        'hdrip',
        'webrip',
        'hdtv',
        'dvd',
        'dvdrip',
        'satrip',
        'tvrip',
        'ppvrip',
        'telecine',
        'scr',
        'telesync',
        'cam',
        'none',
        'n/a'
    ],
    'compressions' => [
        'H264_x264_MPEG-4_AVC',
        'H265_x265_HEVC',
        'AV1',
    ],
    'qualities' => [
        'SDR',
        'HDR',
        'HDR10_HDR10+',
    ],
    'audio_formats' => [
        'AAC',
        'DOLBY_5.1_AC3',
        'DTS',
        'MP3',
        'OGG'
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
