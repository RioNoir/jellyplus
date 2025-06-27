<?php

namespace App\Services\Jellyfin\lib;

use Carbon\Carbon;

class Folder
{
    public static $CONFIG = [
        'CanDelete' => false,
        'CanDownload' => false,
        'BackdropImageTags' => [],
        'ChannelId' => null,
        'ChildCount' => null,
        'Id' => null,
        'ImageBlurHashes' => [],
        'ImageTags' => [],
        'IsFolder' => true,
        'LocationType' => "FileSystem",
        'MediaType' => null,
        'Name' => null,
        'Path' => null,
        'RunTimeTicks' => null,
        'ServerId' => null,
        'SortName' => null,
        'Type' => 'Folder',
    ];
}
