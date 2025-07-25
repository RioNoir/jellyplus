<?php

namespace App\Services\Streams;

class StreamsHelper
{
    public static function getOrderedList(array $list, string $target = null) : array {
        if(isset($target)) {
            $targetIndex = array_search($target, $list);
            if ($targetIndex !== false) {
                $before = array_slice($list, 0, $targetIndex);
                $after = array_slice($list, $targetIndex);
                $list = array_merge($after, $before);
            }
        }
        return $list;
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
