<?php

namespace App\Services\Jellyfin\lib;

class MediaSource
{
    public static $CONFIG = [
          "Protocol" => "Http",
          "Id" => null,
          "Path" => null,
          "Type" => "Default",
          "Container" => "strm",
          "Size" => null,
          "Name" => null,
          "IsRemote" => true,
          "ETag" => null,
          "RunTimeTicks" => null,
          "ReadAtNativeFramerate" => false,
          "IgnoreDts" => false,
          "IgnoreIndex" => false,
          "GenPtsInput" => false,
          "SupportsTranscoding" => true,
          "SupportsDirectStream" => true,
          "SupportsDirectPlay" => true,
          "IsInfiniteStream" => false,
          "UseMostCompatibleTranscodingProfile" => false,
          "RequiresOpening" => false,
          "RequiresClosing" => false,
          "RequiresLooping" => false,
          "SupportsProbing" => true,
          "VideoType" => "VideoFile",
          "MediaStreams" => [],
          "MediaAttachments" => [],
          "Formats" => [],
          "Bitrate" => null,
          "RequiredHttpHeaders" => [],
          "TranscodingUrl" => null,
          "TranscodingSubProtocol" => "http",
          "TranscodingContainer" => null,
          "DefaultAudioStreamIndex" => 0,
          "DefaultSubtitleStreamIndex" => -1,
          "HasSegments" => false,
    ];
}
