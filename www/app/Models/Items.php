<?php

namespace App\Models;

use App\Services\IMDB\IMDBApiManager;
use App\Services\Items\ItemsManager;
use App\Services\Jellyfin\JellyfinItem;
use App\Services\Jellyfin\JellyfinManager;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Items extends Model
{
    protected $table = 'items';
    protected $primaryKey = 'item_id';
    public $timestamps = true;

    public static function findByPath(string $path){
        return self::query()->where('item_path', $path)->first();
    }

    public function getJellyfinItem(){
        if(isset($this->item_jellyfin_id))
            return JellyfinItem::findById($this->item_jellyfin_id)->getResponse();
        return null;
    }

    public function getTitleData($forceOnline = false){
        $titleData = [];

        if (isset($this->item_path) && !$forceOnline) {
            $metaId = $this->item_addon_meta_id;
            if(isset($this->item_imdb_id))
                $metaId = $this->item_imdb_id;

            $json = jp_data_path($this->item_path . '/' . $metaId . '.json');
            if(!file_exists($json))
                $json = $this->item_path.'/'.$this->item_md5.'.json';

            if (file_exists($json))
                $titleData = json_decode(file_get_contents($json), true);
        }

        if (empty($titleData) && isset($this->item_addon_meta_id) && isset($this->item_addon_meta_type))
            $titleData = ItemsManager::getAddonsData($this->item_addon_meta_id, $this->item_addon_meta_type, $this->item_addon_id);

        if(empty($titleData) && isset($this->item_tmdb_id))
            $titleData = ItemsManager::getTmdbData($this->item_tmdb_id, $this->item_type);

        if (empty($titleData) && isset($this->item_imdb_id))
            $titleData = ItemsManager::getImdbData($this->item_imdb_id);

        if(!empty($titleData)){
            if(empty(@$titleData['title']))
                $titleData['title'] = $this->item_title;
            if(empty(@$titleData['poster']))
                $titleData['poster'] = $this->item_image_url;

            $titleData['item_id'] = $this->item_md5;
            $titleData['meta_id'] = $titleData['id'];
            $titleData['meta_type'] = $titleData['type'];
            $titleData['file_id'] = md5($titleData['id']);
            if(isset($titleData['imdb_id'])) {
                $titleData['meta_id'] = $titleData['imdb_id'];
                $titleData['file_id'] = $titleData['imdb_id'];
            }
        }

        return $titleData;
    }

    public function saveItemToLibrary(string $userId = null) : null|string {
        $titleData = $this->getTitleData();
        if(!empty($titleData)) {
            if(isset($userId))
                $this->item_user_id = $userId;
            $this->item_path = ItemsManager::putTitleDataToLocalStorage($titleData);
            $this->save();
        }
        return $this->item_path;
    }

    public function updateItemToLibrary() : null|string {
        $titleData = $this->getTitleData(true);
        if(!empty($titleData)) {
            $this->item_path = ItemsManager::putTitleDataToLocalStorage($titleData, $this->item_path);
            $this->save();
        }
        return $this->item_path;
    }

    public function removeFromLibrary($removeFolder = false): bool {
        if($removeFolder && isset($this->item_path) && file_exists(jp_data_path($this->item_path))) {
            try{
                remove_dir(jp_data_path($this->item_path));
            }catch (\Exception $e){}
        }
        $this->item_path = null;
        $this->item_jellyfin_id = null;
        $this->save();
        return true;
    }

    public function getJellyfinDetailItem($withImdbData = true){
        if($withImdbData)
            $imdbData = $this->getTitleData();

//        $overview = "------------------------------\n\n";
//        $overview .= "⚠️ **How to watch this title**:\n";
//        $overview .= "- Click on the ♥ Heart icon to add this item to the library.\n";
//        $overview .= "- Make sure you have added at least one addon to the library.\n";
//        $overview .= "- Select one link from those available.\n";
//        $overview .= "- Enjoy.\n";
//        $overview .= "------------------------------\n\n";
        $overview = jpt('add_to_library_desc')."\n\n";

        $outcome = \App\Services\Jellyfin\lib\Items::$CONFIG;
        $outcome['CommunityRating'] = @$imdbData['rating'];
        $outcome['DateCreated'] = Carbon::parse($this->created_at)->timestamp;
        $outcome['ProductionYear'] = $this->item_year;
        $outcome['PremiereDate'] = $this->item_year."-01-01T00:00:00.0000000Z";
        if(isset($this->item_imdb_id)) {
            $outcome['ExternalUrls'][] = [
                'Name' => 'IMDb',
                'Url' => 'https://www.imdb.com/title/' . $this->item_imdb_id,
            ];
        }
        $outcome['Genres'] = @$imdbData['genre'];
        $outcome['Id'] = $this->item_md5;
        $outcome['ImageTags']['Primary'] = $this->item_image_md5;
        $outcome['Name'] = @$imdbData['title'] ?? $this->item_title;
        $outcome['OriginalTitle'] = @$imdbData['originaltitle'] ?? $this->item_original_title;
        $outcome['Overview'] = $overview . (@$imdbData['plot'] ?? $this->item_description);
        $outcome['ParentId'] = $this->item_md5;
        $outcome['ProviderIds']['Imdb'] = @$imdbData['imdb_id'] ?? $this->item_imdb_id;
        $outcome['ProviderIds']['Tmdb'] = @$imdbData['tmdb_id'] ?? $this->item_tmdb_id;
        $outcome['ServerId'] = jp_config('server_id');
        $outcome['SortName'] = $this->item_title;
        //$outcome['Type'] = $this->item_type == "tvSeries" ? 'Series' : 'Movie';
        //$outcome['Type'] = "Unknown";
        $outcome['EnableMediaSourceDisplay'] = false;
        $outcome['Type'] = "Movie";
        $outcome['Path'] = null;
        $outcome['MediaStreams'] = null;
        $outcome['MediaSources'] = [];
        $outcome['VideoType'] = 'Unknown';
        $outcome['MediaType'] = 'Unknown';
        $outcome['LocationType'] = 'Remote';
        $outcome['UserData'] = [];

        if(isset($this->item_path) || Cache::has('item_favorite_'.$this->item_md5)){
            $outcome['UserData'] = [
                'PlaybackPositionTicks' => 0,
                'PlayCount' => 0,
                'IsFavorite' => isset($this->item_path) || (bool)Cache::get('item_favorite_' . $this->item_md5, false),
                'Played' => false,
                'Key' => null,
                'ItemId' => '00000000000000000000000000000000'
            ];
        }

        return $outcome;
    }

    public function getJellyfinListItem($type = "CollectionFolder"){
        $outcome = \App\Services\Jellyfin\lib\Items::$CONFIG;
        $outcome = array_merge($outcome, [
            'Name' => $this->item_title,
            'ServerId' => jp_config('server_id'),
            'Id' => $this->item_jellyfin_id ?? $this->item_md5,
            'PremiereDate' => $this->item_year."-01-01T00:00:00.0000000Z",
            'CriticRating' => null,
            'OfficialRating' => null,
            'ChannelId' => null,
            'CommunityRating' => null,
            'ProductionYear' => $this->item_year,
            'IsFolder' => false,
            //'Type' => $type,
            //'Type' => 'Unknown',
            //'Type' => $this->item_type == "tvSeries" ? 'Series' : 'Movie',
            'Type' => 'Movie',
            'PrimaryImageAspectRatio' => 0.7,
            'UserData' => [
                'PlaybackPositionTicks' => 0,
                'PlayCount' => 0,
                'IsFavorite' => isset($this->item_path) || (bool)Cache::get('item_favorite_' . $this->item_md5, false),
                'Played' => false,
                'Key' => null,
                'ItemId' => '00000000000000000000000000000000'
            ],
            'VideoType' => 'Unknown',
            //'VideoType' => 'VideoFile',
            'ImageTags' => [
                'Primary' => $this->item_image_md5
            ],
            //'LocationType' => 'FileSystem',
            'LocationType' => 'Remote',
            'MediaType' => 'Unknown',
            //'MediaType' => 'Video',
        ]);

        if(isset($this->item_image_md5))
            $outcome['ImageTags']['Primary'] = $this->item_image_md5;

        return $outcome;
    }
}
