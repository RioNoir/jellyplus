<?php

namespace App\Services\Streams;

use Illuminate\Database\Eloquent\Collection;

class StreamCollection extends Collection
{
    public static function findByMetaId(string $metaId, string $metaType = null){
        $streams = StreamsManager::getStreams($metaId, $metaType);
        return new static($streams);
    }

    public function sortByStreamId(string $streamId){
        return $this->sortBy(function ($video) use ($streamId) {
            return (stripos($video['stream_md5'], $streamId) !== false) ? -1 : 1;
        });
    }

    public function filterByStreamId(string $streamId){
        return $this->filter(function ($stream) use ($streamId) {
            return $stream['stream_md5'] == $streamId;
        });
    }

    public function sortByOptions(string $resolution = null, string $format = null, string $compression = null,
                                  string $quality = null, string $audio = null,  string $language = null){
        if(empty($resolution))
            $resolution = jp_config('stream.resolution');
        if(empty($format))
            $format = jp_config('stream.format');
        if(empty($compression))
            $compression = jp_config('stream.compression');
        if(empty($quality))
            $quality = jp_config('stream.quality');
        if(empty($audio))
            $audio = jp_config('stream.audio_format');
        if(empty($language))
            $language = jp_config('stream.lang');

        return $this->sortByOption(jp_config('stream.audio_formats'), $audio, false)
            ->sortByOption(jp_config('stream.formats'), $format, false)
            ->sortByOption(jp_config('stream.compressions'), $compression)
            ->sortByOption(jp_config('stream.qualities'), $quality, false)
            ->sortByOption(jp_config('stream.resolutions'), $resolution)
            ->sortByLanguage($language)
            ;
    }

    public function sortByKeywords($keywords = null){
        if(empty($keywords))
            $keywords = jp_config('stream.sortby_keywords');
        if(!empty($keywords)) {
            return $this->sortBy(function ($stream) use ($keywords) {
                $title = strtolower(str_replace("\n"," ", trim($stream['stream_title'])));
                foreach ($keywords as $index => $keyword) {
                    if (stripos($title, $keyword) !== false) {
                        return $index;
                    }
                }
                return count($keywords);
            });
        }
        return $this;
    }

    public function sortByLanguage(string $language = "en-US"){
        $languages = StreamsHelper::getOrderedLanguages($language);
        return $this->sortBy(function ($stream) use ($languages) {
            $title = strtolower(str_replace("\n"," ", trim($stream['stream_title'])));
            foreach ($languages as $index => $lang) {
                if (stripos($title, $lang) !== false) {
                    return $index;
                }
            }
            return count($languages);
        });
    }

    public function sortByOption(array $list, string $target, bool $returnEffective = true){
        $list = StreamsHelper::getOrderedList($list, $target);
        return $this->sortBy(function ($stream) use ($list, $target, $returnEffective) {
            $title = strtolower(str_replace("\n"," ", trim($stream['stream_title'])));
            foreach ($list as $index => $search) {
                if(str_contains($search, '_')){
                    $searches = explode('_', $search);
                    foreach ($searches as $index2 => $searchKey) {
                        if (stripos($title, $searchKey) !== false) {
                            return $index;
                        }
                    }
                }
                if (stripos($title, $search) !== false)
                    return $index;
            }

            if(!$returnEffective){
                return -(count($list) - (int) array_search($target, $list));
            }else{
                return count($list);
            }
        });
    }

    public function filterByFormats(){
        $excludedFormats = jp_config('stream.excluded_formats');
        return $this->filter(function ($stream) use($excludedFormats){
            $title = strtolower(str_replace("\n"," ", trim($stream['stream_title'])));
            foreach ($excludedFormats as $format) {
                if (stripos($title, $format) !== false) {
                    return false;
                }
            }
            return true;
        });
    }

    public function filterByUrls(){
        return $this->filter(function ($stream) {
            return (bool) $stream->getStreamUrl();
        });
    }

    public function firstByUrl(){
        foreach ($this->all() as $stream) {
            $streamUrl = $stream->getStreamUrl();
            if($streamUrl)
                return $stream;
        }
        return null;
    }

}
