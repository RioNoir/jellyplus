<?php

namespace App\Services\Jellyfin\lib;

class TVSeries
{
    public static $FOLDER_CONFIG = [
        "LibraryOptions" => [
            "Enabled" => true,
            "EnableArchiveMediaFiles" => false,
            "EnablePhotos" => true,
            "EnableRealtimeMonitor" => true,
            "EnableLUFSScan" => true,
            "ExtractTrickplayImagesDuringLibraryScan" => false,
            "SaveTrickplayWithMedia" => false,
            "EnableTrickplayImageExtraction" => false,
            "ExtractChapterImagesDuringLibraryScan" => false,
            "EnableChapterImageExtraction" => false,
            "EnableInternetProviders" => true,
            "SaveLocalMetadata" => true,
            "EnableAutomaticSeriesGrouping" => false,
            "PreferredMetadataLanguage" => "",
            "MetadataCountryCode" => "",
            "SeasonZeroDisplayName" => "Specials",
            "AutomaticRefreshIntervalDays" => 60,
            "EnableEmbeddedTitles" => false,
            "EnableEmbeddedExtrasTitles" => false,
            "EnableEmbeddedEpisodeInfos" => true,
            "AllowEmbeddedSubtitles" => "AllowAll",
            "SkipSubtitlesIfEmbeddedSubtitlesPresent" => false,
            "SkipSubtitlesIfAudioTrackMatches" => false,
            "SaveSubtitlesWithMedia" => true,
            "SaveLyricsWithMedia" => false,
            "RequirePerfectSubtitleMatch" => true,
            "AutomaticallyAddToCollection" => false,
            "PreferNonstandardArtistsTag" => false,
            "UseCustomTagDelimiters" => false,
            "MetadataSavers" => [
                "Nfo"
            ],
            "TypeOptions" => [
                [
                    "Type" => "Series",
                    "MetadataFetchers" => [
                        "TheMovieDb",
                        "The Open Movie Database"
                    ],
                    "MetadataFetcherOrder" => [
                        "TheMovieDb",
                        "The Open Movie Database"
                    ],
                    "ImageFetchers" => [
                        "TheMovieDb",
                        "The Open Movie Database"
                    ],
                    "ImageFetcherOrder" => [
                        "TheMovieDb",
                        "The Open Movie Database"
                    ],
                    "ImageOptions" => [
                        [
                           "Type" => "Primary",
                           "Limit" => 1,
                           "MinWidth" => 0,
                        ],
                        [
                            "Type" => "Art",
                            "Limit" => 0,
                            "MinWidth" => 0,
                        ],
                        [
                            "Type" => "BoxRear",
                            "Limit" => 0,
                            "MinWidth" => 0,
                        ],
                        [
                            "Type" => "Banner",
                            "Limit" => 1,
                            "MinWidth" => 0,
                        ],
                        [
                            "Type" => "Box",
                            "Limit" => 0,
                            "MinWidth" => 0,
                        ],
                        [
                            "Type" => "Disc",
                            "Limit" => 0,
                            "MinWidth" => 0,
                        ],
                        [
                            "Type" => "Logo",
                            "Limit" => 1,
                            "MinWidth" => 0,
                        ],
                        [
                            "Type" => "Menu",
                            "Limit" => 0,
                            "MinWidth" => 0,
                        ],
                        [
                            "Type" => "Thumb",
                            "Limit" => 1,
                            "MinWidth" => 0,
                        ],
                        [
                            "Type" => "Backdrop",
                            "Limit" => 1,
                            "MinWidth" => 1280,
                        ],
                    ]
                ],
                [
                    "Type" => "Season",
                    "MetadataFetchers" => [
                        "TheMovieDb"
                    ],
                    "MetadataFetcherOrder" => [
                        "TheMovieDb"
                    ],
                    "ImageFetchers" => [
                        "TheMovieDb"
                    ],
                    "ImageFetcherOrder" => [
                        "TheMovieDb"
                    ],
                ],
                [
                    "Type" => "Episode",
                    "MetadataFetchers" => [
                        "TheMovieDb",
                        "The Open Movie Database"
                    ],
                    "MetadataFetcherOrder" => [
                        "TheMovieDb",
                        "The Open Movie Database"
                    ],
                    "ImageFetchers" => [
                        "TheMovieDb",
                        "The Open Movie Database",
                        "Embedded Image Extractor",
                        "Screen Grabber"
                    ],
                    "ImageFetcherOrder" => [
                        "TheMovieDb",
                        "The Open Movie Database",
                        "Embedded Image Extractor",
                        "Screen Grabber"
                    ],
                ]
            ],
            "LocalMetadataReaderOrder" => ["Nfo"],
            "SubtitleDownloadLanguages" => [],
            "CustomTagDelimiters" => [
                "/",
                "|",
                ";",
                "\\",
            ],
            "DelimiterWhiteList" => [],
            "DisabledSubtitleFetchers" => [],
            "SubtitleFetcherOrder" => [],
            "DisabledLyricFetchers" => [],
            "LyricFetcherOrder" => [],
            "PathInfos" => [
                [
                    "Path" => "/data/library/tvSeries"
                ]
            ]
        ]
    ];
}
