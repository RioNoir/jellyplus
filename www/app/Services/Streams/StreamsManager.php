<?php

namespace App\Services\Streams;

use App\Models\Streams;
use App\Services\Addons\AddonsApiManager;
use App\Services\Jellyfin\JellyfinApiManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StreamsManager
{
    public static function getStreams(string $metaId, string $metaType = null, string $mediaSourceId = null, string $userId = null) {
        $typesMap = ['movie' => 'movie', 'tvSeries' => 'series', 'liveTv' => 'tv'];
        $metaType = @$typesMap[$metaType] ?? $metaType;
        set_time_limit(-1);

        Log::info('[stream] Search streams for: ' .$metaId);

        $streams = self::searchStreamsFromAddons($metaId, $metaType, $mediaSourceId, $userId);

        Log::info('[stream] Founded '.count($streams).' streams for: ' .$metaId);

        return $streams;
    }

    protected static function searchStreamsFromAddons(string $metaId, string $metaType = null, string $mediaSourceId = null, string $userId = null){

        $addons = AddonsApiManager::getAddonsByResource('stream');
        $now = Carbon::now()->format('YmdH');
        $key = 'streams_addons_'.md5($now.$metaId.$metaType.$mediaSourceId.json_encode($addons).json_encode(jp_config()));

        return Cache::remember($key, Carbon::now()->addMinutes(jp_config('stream.cache_ttl')), function()
            use($addons, $metaId, $metaType, $mediaSourceId, $userId){
            $streams = [];
            try {
                if(isset($metaType)){
                    $addons = array_filter(array_map(function($addon) use ($metaType){
                        return in_array($metaType, $addon['repository']['types']) ? $addon : null;
                    }, $addons));
                }
                $addons = array_filter(array_map(function($addon) use ($metaId){
                    return (empty(@$addon['repository']['prefixes']) || str_contains_arr($metaId, $addon['repository']['prefixes'])) ? $addon : null;
                }, $addons));

                foreach ($addons as $addon) {
                    $api = new AddonsApiManager($addon['repository']['endpoint']);
                    if(isset($metaType)){
                        $sources = $api->getStreams($metaId, $metaType);
                    }else{
                        if (str_contains($metaId, ':') && substr_count($metaId, ':') > 2) {
                            $sources = $api->getSeriesEpisode($metaId) ?? [];
                        } else {
                            $sources = $api->getMovie($metaId) ?? [];
                        }
                    }

                    $count = 0;
                    if (!empty($sources)) {
                        foreach ($sources as $source) {
                            $stream = self::getStreamFromSource($source, $addon, $metaId, $mediaSourceId, $userId);
                            if (!empty($stream)) {
                                $count++;
                                $streams[$stream->stream_md5] = $stream;
                            }
                        }
                    }

                    Log::info('[stream] Search '.$metaId.' on '.@$addon['repository']['name']. ": ".count($sources). " results found, ".$count." streams found.");
                }
            }catch (\Exception $e){}
            return $streams;
        });
    }

    protected static function getStreamFromSource(array $source, array $addon = [], string $metaId = null, string $mediaSourceId = null, string $userId = null){

        //Torrent url
        if (isset($source['infoHash'])) {
            $source['url'] = "magnet:?xt=urn:btih:" . strtoupper($source['infoHash']);
            if (isset($source['behaviorHints']['filename']))
                $source['magnet']['filename'] = $source['behaviorHints']['filename'];
            if(isset($source['fileIdx'])) {
                $source['magnet']['index'] = $source['fileIdx'];
                if (isset($source['sources'])) {
                    $trackers = array_filter(array_map(function ($source) {
                        return str_starts_with($source, 'tracker:') ? str_replace('tracker:', '', $source) : null;
                    }, $source['sources']));
                    foreach ($trackers as $tracker) {
                        $source['url'] .= '&tr=' . urlencode($tracker);
                    }
                }
            }
            $source['magnet']['url'] = $source['url'];
        }

        //Save Stream url
        if (isset($source['url'])) {
            if(!in_array(parse_url($source['url'], PHP_URL_PATH), jp_config('stream.excluded_paths'))){
                $file = @pathinfo(@parse_url($source['url'])['path']);
                $container = 'hls';
                if (!empty(@$file['extension']) && in_array(@$file['extension'], jp_config('jellyfin.supported_extensions')))
                    $container = $file['extension'];

                $title = @$source['name'];
                $description = "";
                if (!empty(@$source['title']))
                    $description .= str_replace("\n", " ", $source['title']);
                if (!empty(@$source['description']))
                    $description .= str_replace("\n", " | ", $source['description']);

                //Langs
                $langs = array_map(function ($lang) {
                    return lang2flag($lang);
                }, array_filter(array_map(function ($emoji) {
                    return flag2lang($emoji);
                }, text2emoji($description)), function ($string) {
                    return is_string($string) && preg_match('/^[\p{L}\p{Zs}]+$/u', $string);
                }));
                $api = new JellyfinApiManager();
                $lang = $api->getStreamingLanguageByUser($userId);
                $langIndex = array_search(lang2flag(substr($lang, 0, 2)), $langs);

                $filename = "";
                if (isset($source['behaviorHints']['filename']))
                    $filename = $source['behaviorHints']['filename'];

                if(!empty($langs) && $langIndex !== false)
                    $title .= " | " . $langs[$langIndex];
                if (!empty($filename)) {
                    $title .= " | " . $filename;
                } elseif (!empty($description)) {
                    $title .= " | " . $description;
                }

                $title = str_replace("\n", ' ', $title);

                //Filter by torrent matches
                if(isset($source['infoHash']) && !empty(jp_config('stream.exclude_torrent_sources')) &&
                    (int) jp_config('stream.exclude_torrent_sources') == 1)
                    return [];

                //Filter by language
                if(!empty(jp_config('stream.only_language_match')) && (int) jp_config('stream.only_language_match') == 1 &&
                    !str_contains($title, lang2flag(substr($lang, 0, 2))) &&
                    !str_contains($title, substr($lang, 0, 2)) &&
                    !str_contains($title, $lang) &&
                    !str_contains($title, \Locale::getDisplayName(substr($lang, 0, 2), "en")) &&
                    !str_contains($title, \Locale::getDisplayName(substr($lang, 0, 2), substr($lang, 0, 2))))
                    return [];

                //Filter by keywords
                if(!empty(jp_config('stream.included_keywords')) && !str_contains_arr($title, jp_config('stream.included_keywords')))
                    return [];
                if(!empty(jp_config('stream.excluded_keywords')) && str_contains_arr($title, jp_config('stream.excluded_keywords')))
                    return [];

                $md5 = md5($source['url']);

                $stream = new Streams();
                $stream->stream_md5 = $md5;
                $stream->stream_url = $source['url'];
                $stream->stream_protocol = isset($source['infoHash']) ? "torrent" : "http";
                $stream->stream_container = $container;
                $stream->stream_addon_id = @$addon['repository']['id'];
                //$stream->stream_imdb_id = $imdbId;
                $stream->stream_meta_id = $metaId;
                $stream->stream_jellyfin_id = $mediaSourceId;
                $stream->stream_title = $title;
                $stream->stream_host = @$addon['repository']['host'];
                $stream->stream_info = json_encode($source);
                //$stream->save();

                Log::info('[stream] Saving stream '.$title . ' ('.$md5.')');

                return $stream;
            }
        }

        return [];
    }

}
