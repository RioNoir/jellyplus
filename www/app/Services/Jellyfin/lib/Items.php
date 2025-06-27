<?php

namespace App\Services\Jellyfin\lib;

use Carbon\Carbon;

class Items
{

    public static $CONFIG = [
        'CanDelete' => false,
        'CanDownload' => false,
        'ChannelId' => null,
        'Chapters' => [],
        'CommunityRating' => null,
        'CriticRating' => null,
        'DateCreated' => null,
        'DisplayPreferencesId' => null,
        'EnableMediaSourceDisplay' => false,
        'Etag' => null,
        'ExternalUrls' => [],
        'Genres' => [],
        'GenreItems' => [],
        'Id' => null,
        'ImageBlurHashes' => [],
        'ImageTags' => [
            "Primary" => null,
        ],
        'IsFolder' => false,
        'LocalTrailerCount' => 0,
        'LocationType' => 'FileSystem',
        'LockData' => true,
        'LockedFields' => [],
        'MediaSources' => [],
        'MediaStreams' => [],
        'MediaType' => "Unknown",
        'Name' => "",
        'OfficialRating' => null,
        'OriginalTitle' => "",
        'Overview' => "",
        'ParentId' => null,
        'Path' => "",
        'People' => [],
        'PlayAccess' => 'Full',
        'PremiereDate' => null,
        'PrimaryImageAspectRatio' => 0.7,
        'ProductionLocations' => [],
        'ProductionYear' => null,
        'ProviderIds' => [],
        'RemoteTrailers' => [],
        'ServerId' => null,
        'SortName' => null,
        'SpecialFeatureCount' => 0,
        'Studios' => [],
        'Taglines' => [],
        'Tags' => [],
        'Trickplay' => [],
        'Type' => null,
        'UserData' => [],
        'VideoType' => 'VideoFile',
    ];
}
