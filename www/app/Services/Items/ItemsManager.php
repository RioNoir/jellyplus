<?php

namespace App\Services\Items;

use App\Models\Items;
use App\Services\Addons\AddonsApiManager;
use App\Services\IMDB\IMDBApiManager;
use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\Jellyfin\JellyfinManager;
use App\Services\TMDB\TMDBApiManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ItemsManager
{
    protected static $libraryPath = 'library';

    protected $model;
    public function __construct(Items $model){
        $this->model = $model;
    }

    public static function getImdbData(string $imdbId){
        $api = new IMDBApiManager();
        $imdbData = $api->getTitleDetails($imdbId);
        return !empty($imdbData) ? $imdbData : [];
    }

    public static function getTmdbData(string $tmdbId, string $type){
        $api = new TMDBApiManager();
        $tmdbData = $api->getTitleDetails($tmdbId, $type);
        return !empty($tmdbData) ? $tmdbData : [];
    }

    public static function getAddonsData(string $itemAddonId, string $type, string $addonId = null){
        $api = new AddonsApiManager();
        $addonData = $api->getTitleDetails($itemAddonId, $type, $addonId);
        return !empty($addonData) ? $addonData : [];
    }

    //deprecated
    public static function getImdbDataFromLocalStorage(string $imdbId, string $imdbType = null){
        $directory = jp_data_path(self::$libraryPath.'/'.Str::plural($imdbType).'/'.$imdbId);
        $file = $directory.'/'.$imdbId. '.json';
        if(file_exists($file) && !Carbon::parse(filemtime($file))->isBefore(Carbon::now()->subDays(2))) {
            return json_decode(file_get_contents($file), true);
        }
        return [];
    }

    public static function imdbDataToDatabase(array $imdbData) : null|Items {
        if(!empty($imdbData)){
            if(isset($imdbData['imdb_id'])) {
                $item = Items::query()->where('item_imdb_id', $imdbData['imdb_id'])->first();
            }else{
                $item = Items::query()->where('item_md5', md5($imdbData['id']))->first();
            }

            if(!isset($item)) {
                $item = new Items();
                $item->item_md5 = @md5(@$imdbData['id']);
            }

            $item->item_imdb_id = @$imdbData['imdb_id'];
            $item->item_tmdb_id = @$imdbData['tmdb_id'];
            $item->item_type = @$imdbData['type'];
            $item->item_title = @$imdbData['title'];
            $item->item_original_title = @$imdbData['originaltitle'];
            $item->item_year = @$imdbData['year'];
            $item->item_image_url = @$imdbData['poster'];
            $item->item_image_md5 = @md5(@$imdbData['poster']);
            $item->item_server_id = jp_config('server_id');
            $item->item_addon_meta_id = @$imdbData['addon_meta_id'];
            $item->item_addon_meta_type = @$imdbData['addon_meta_type'];
            $item->item_addon_id = @$imdbData['addon_id'];

            $item->save();

            return $item;
        }
        return null;
    }

    /**
     * @throws \Exception
     */
    public static function putTitleDataToLocalStorage(array $titleData, string $itemPath = null): ?string {
        if(!empty($titleData) && in_array($titleData['type'], ['movie', 'tvSeries', 'liveTv'])) {
            $path = $itemPath ?? self::$libraryPath.'/'.Str::plural($titleData['type']).'/'.$titleData['file_id'];
            $file = '/' . $titleData['file_id'] . '.json';

            $directory = jp_data_path($path);
            if (!file_exists($directory))
                mkdir($directory, 0777, true);

            JellyfinManager::createStructure($directory, $titleData);

            file_put_contents($directory . $file, json_encode($titleData, JSON_PRETTY_PRINT));
            return $path;
        }
        return null;
    }
}
