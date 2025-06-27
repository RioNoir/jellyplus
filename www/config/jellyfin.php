<?php

return [
    'url' => 'http://localhost:8097',
    'external_url' => 'http://localhost:8096',
    'library_path' => jp_data_path('library'),
    'movies_path' => jp_data_path('library/movies'),
    'series_path' => jp_data_path('library/tvSeries'),
    'tv_path' => jp_data_path('library/liveTvs'),
    'discover_path' => jp_data_path('jellyfin/metadata/views/'.md5("_discover")),
    'virtual_folders' => [
        'Movies' => [
            'name' => 'Movies',
            'path' => jp_data_path('library/movies'),
            'type' => 'movies',
        ],
        'TV Series' => [
            'name' => 'TV Series',
            'path' => jp_data_path('library/tvSeries'),
            'type' => 'tvshows',
        ],
        'Live TV' => [
            'name' => 'Live TV',
            'path' => jp_data_path('library/liveTvs'),
            'type' => 'movies',
        ]
    ],
    'delete_unused_items_after' => 1, //days
    'update_series_after' => 24, //hours
    'delete_streams_after' => 3, //hours
    'update_playback_limit' => 10,
    'supported_extensions' => [
        'mp4',
        'mkv',
        'webm',
        'ts',
        'm2t',
        'm3u',
        'm3u8'
    ]
];
