<?php

namespace App\Services\Streams;

class StreamsHelper
{
    public static function getOrderedResolutions(string $streamResolution = null) : array {
        $streamResolutions = jp_config('stream.resolutions');
        if(isset($streamResolution)) {
            $targetIndex = array_search($streamResolution, $streamResolutions);
            if ($targetIndex !== false) {
                $before = array_slice($streamResolutions, 0, $targetIndex);
                $after = array_slice($streamResolutions, $targetIndex);
                $streamResolutions = array_merge($after, $before);
            }
        }
        return $streamResolutions;
    }

    public static function getOrderedFormats(string $streamFormat = null){
        $streamFormats = jp_config('stream.formats');
        if(isset($streamFormat)) {
            $targetIndex = array_search($streamFormat, $streamFormats);
            if ($targetIndex !== false) {
                $before = array_slice($streamFormats, 0, $targetIndex);
                $after = array_slice($streamFormats, $targetIndex);
                $streamFormats = array_merge($after, $before);
            }
        }
        return $streamFormats;
    }

    public static function getOrderedLanguages(string $streamLang) : array {
        //$streamLang = strtolower(\Locale::getDisplayLanguage($streamLang, 'en'));
        return [
            strtolower(\Locale::getDisplayLanguage(substr($streamLang, 0, 2), 'en')),
            //strtolower(\Locale::getDisplayLanguage(substr($streamLang, 0, 2), substr($streamLang, 0, 2))),
            lang2flag(substr($streamLang, 0, 2)),
            $streamLang,
            substr($streamLang, 0, 3),
            //substr($streamLang, 0, 2),
            "sub ".$streamLang,
            "sub ".substr($streamLang, 0, 3),
            //"sub ".substr($streamLang, 0, 2),
            "sub-".$streamLang,
            "sub-".substr($streamLang, 0, 3),
            //"sub-".substr($streamLang, 0, 2),
            "dual audio",
            "dual-audio",
            "multi-audio",
            "multi audio",
            "subs",
            "multi-sub",
            "multi sub",
            "multi-subs",
            "multi subs",
            "multiple subtitle",
        ];
    }
}
