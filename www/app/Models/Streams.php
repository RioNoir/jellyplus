<?php

namespace App\Models;

use App\Services\Jellyfin\JellyfinItem;
use App\Services\Jellyfin\JellyfinManager;
use App\Services\Jellyfin\lib\MediaSource;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Streams extends Model
{
    protected $table = 'streams';
    protected $primaryKey = 'stream_id';
    public $timestamps = true;

    public function getStreamUrl($excludePaths = true){
        $model = $this;
        $key = 'stream_url_'.md5($model->stream_md5.$excludePaths.Carbon::now()->format('YmdH'));
        return Cache::remember($key, Carbon::now()->addMinutes(2), function () use($model, $excludePaths){
            if(isset($model->stream_url)) {
                $streamUrl = null;
                if ($model->stream_protocol == "torrent"){
                    $sourceInfo = $model->getSourceInfo('magnet');
                    if(!empty($sourceInfo) && isset($sourceInfo['url'])){
                        $streamUrl = app_url('/stream-torrent/' . urlencode($sourceInfo['url']));
                        if(isset($sourceInfo['index'])) {
                            $streamUrl .= '?fileIndex=' . $sourceInfo['index'];
                        }elseif (isset($sourceInfo['filename'])) {
                            $streamUrl .= '?file=' . urlencode($sourceInfo['filename']);
                        }
                    }
                }
                else{
                    $streamUrl = $excludePaths ? get_last_url($model->stream_url) : $model->stream_url;
                }

                if (isset($streamUrl) && ($excludePaths || !in_array(parse_url($streamUrl, PHP_URL_PATH), jp_config('stream.excluded_paths'))))
                    return $streamUrl;
            }
            return false;
        });
    }

    public function getSourceInfo($key = null){
        if(isset($this->stream_info)) {
            $sourceInfo = json_decode($this->stream_info, true);
            if (!isset($key))
                return $sourceInfo;

            return data_get($sourceInfo, $key);
        }
        return null;
    }

    public function getItem(string $itemId = null){
        if(!isset($itemId)){
            $metaId = $this->stream_meta_id;
            if(isset($this->stream_imdb_id))
                $metaId = $this->stream_imdb_id;

            $metaId = @explode(':', @urldecode($metaId))[0];
            if(isset($metaId)){
                return Items::query()
                    ->where('item_addon_meta_id', $metaId)
                    ->orWhere('item_imdb_id', $metaId)->first();
            }
        }else{
            return Items::query()->where('item_md5', $itemId)->first();
        }
        return null;
    }

    public function getItemPath(string $itemId = null){
        $item = $this->getItem($itemId);
        if(isset($item->item_path)){
            $metaId = $this->stream_meta_id;
            if(isset($this->stream_imdb_id))
                $metaId = $this->stream_imdb_id;

            $path = $item->item_path.'/'.$metaId.'.strm';
            if(!file_exists($path))
                $path = $item->item_path.'/'.$item->item_md5.'.strm';

            if($item->item_type == "tvSeries"){
                $metaId = explode(':', $metaId);
                if(count($metaId) > 1){
                    $season = "Season " . sprintf("%02d", $metaId[1]);
                    $episode = "Episode S".sprintf("%02d", $metaId[1])."E".sprintf("%02d", @$metaId[2]);
                    $path = $item->item_path.'/'.$season.'/'.$episode.'.strm';
                }
            }

            if(file_exists(jp_data_path($path)))
                return jp_data_path($path);
        }
        return null;
    }

    public function getJellyfinItem(){
        $item = $this->getItem();
        if(!empty($item) && isset($item->item_jellyfin_id)){
            return JellyfinItem::findById($item->item_jellyfin_id)->getResponse();
        }
        return null;
    }

    public function getJellyfinMediaSource(){
        $mediaSource = MediaSource::$CONFIG;
        $mediaSource['Id'] = $this->stream_md5;
        $mediaSource['ETag'] = $this->stream_md5;
        $mediaSource['Path'] = $this->stream_url;
        $mediaSource['Name'] = $this->stream_title;
        return $mediaSource;
    }

}
