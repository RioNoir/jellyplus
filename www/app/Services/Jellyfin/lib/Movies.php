<?php

namespace App\Services\Jellyfin\lib;

class Movies
{
    public static $FOLDER_CONFIG = [
        'LibraryOptions' => [
            'Enabled' => true,
            'EnableArchiveMediaFiles' => false,
            'EnablePhotos' => true,
            'EnableRealtimeMonitor' => true,
            'EnableLUFSScan' => true,
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
            "EnableEmbeddedEpisodeInfos" => false,
            "AllowEmbeddedSubtitles" => "AllowAll",
            "SkipSubtitlesIfEmbeddedSubtitlesPresent" => false,
            "SkipSubtitlesIfAudioTrackMatches" => false,
            "SaveSubtitlesWithMedia" => true,
            "SaveLyricsWithMedia" => false,
            "RequirePerfectSubtitleMatch" => true,
            "AutomaticallyAddToCollection" => true,
            "PreferNonstandardArtistsTag" => false,
            "UseCustomTagDelimiters" => false,
            "MetadataSavers" => ["Nfo"],
            "TypeOptions" => [
                [
                    "Type" => "Movie",
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
                    "Path" => "/data/library/movies"
                ]
            ]
        ]
    ];


}
