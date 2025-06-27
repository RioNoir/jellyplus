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

    public function sortByOptions(string $resolution = null, string $format = null, string $language = null){
        if(empty($resolution))
            $resolution = jp_config('stream.resolution');
        if(empty($format))
            $format = jp_config('stream.format');
        if(empty($language))
            $language = jp_config('stream.lang');

        return $this->sortByResolution($resolution)->sortByLanguage($language);
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

    public function sortByResolution(string $resolution = '1080p'){
        $resolutions = StreamsHelper::getOrderedResolutions($resolution);
        return $this->sortBy(function ($stream) use ($resolutions) {
            $title = strtolower(str_replace("\n"," ", trim($stream['stream_title'])));
            foreach ($resolutions as $index => $resolution) {
                if (stripos($title, $resolution) !== false) {
                    return $index;
                }
            }
            return count($resolutions);
        });
    }

    public function sortByFormat(string $format = 'bluray'){
        $formats = StreamsHelper::getOrderedFormats($format);
        return $this->sortBy(function ($stream) use ($formats) {
            $title = strtolower(str_replace("\n"," ", trim($stream['stream_title'])));
            foreach ($formats as $index => $format) {
                if (stripos($title, $format) !== false) {
                    return $index;
                }
            }
            return count($formats);
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
