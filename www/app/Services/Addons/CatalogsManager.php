<?php

namespace App\Services\Addons;

use App\Models\Items;
use App\Services\Helpers\ImageHelper;
use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\Jellyfin\JellyfinItem;
use App\Services\Jellyfin\lib\Folder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CatalogsManager
{
    public static function hasCatalogs(){
        $addons = AddonsApiManager::getAddonsByResource('catalog');
        return count($addons) > 0;
    }

    public static function getCatalogs(){
        $addons = AddonsApiManager::getAddonsByResource('catalog');
        $items = [];
        foreach ($addons as $addon) {
            foreach ($addon['manifest']['catalogs'] as $catalog) {
                try {
                    if (empty($catalog['name']))
                        continue;

                    $catalogType = str_replace('.', '+', $catalog['type']);
                    $catalogId = str_replace('.', '+', $catalog['id']);
                    $items[$catalogType]['name'] = ucfirst($catalogType);
                    $hasSkip = !empty(array_filter(array_map(function ($item) {
                        return @$item['name'] == "skip" ? true : false;
                    }, @$catalog['extra'] ?? [])));

                    $folder = [
                        'name' => $catalog['name'],
                        'params' => [
                            'addon_id' => $addon['repository']['id'],
                            'type' => $catalogType,
                            'id' => $catalogId,
                            'has_skip' => $hasSkip,
                            'query' => []
                        ],
                    ];

                    if (!empty($catalog['extra'])) {
                        foreach ($catalog['extra'] as $extra) {
                            if (empty($extra['isRequired'])) {
                                $folder['folders']['all'] = [
                                    'name' => 'All',
                                    'params' => [
                                        'addon_id' => $addon['repository']['id'],
                                        'type' => $catalogType,
                                        'id' => $catalogId,
                                        'has_skip' => $hasSkip,
                                        'query' => []
                                    ],
                                ];
                            }

                            if (!empty($extra['options'])) {
                                foreach ($extra['options'] as $option) {
                                    $folder['folders'][] = [
                                        'name' => $option,
                                        'params' => [
                                            'addon_id' => $addon['repository']['id'],
                                            'type' => $catalogType,
                                            'id' => $catalogId,
                                            'has_skip' => $hasSkip,
                                            'query' => [
                                                $extra['name'] => $option,
                                            ]
                                        ],
                                    ];
                                }
                            }
                        }
                    }

                    $items[$catalogType]['folders'][$catalogId] = $folder;
                }catch (\Exception $e){}
            }
        }
        return $items;
    }

    public static function getCatalogCollection($childCount = 1){
        $path = config('jellyfin.discover_path');
        if(!file_exists($path))
            mkdir($path, 0777, true);

        $catalog = \App\Services\Jellyfin\lib\Folder::$CONFIG;
        $catalog["Name"] = t("Discover");
        $catalog["ServerId"] = jp_config('server_id');
        $catalog["Id"] = md5("_discover");
        $catalog["ParentId"] = md5("_discover");
        $catalog["Etag"] = md5("_discover");
        $catalog["Path"] = $path;
        $catalog['ImageBlurHashes'] = [];
        $catalog["LockData"] = true;
        $catalog['ImageTags'] = [
            'Primary' => md5("_discover")
        ];
        $catalog["DateCreated"] = jellyfin_date(Carbon::now()->format('Y-m-d H:i:s'));
        $catalog["SortName"] = "discover";
        $catalog["EnableMediaSourceDisplay"] = true;
        $catalog["PlayAccess"] = "Full";
        $catalog["Type"] = "CollectionFolder";
        //$catalog["CollectionType"] = "playlists";
        $catalog["CollectionType"] = "boxsets";
        $catalog["LocationType"] = "FileSystem";
        $catalog["MediaType"] = "Unknown";
        $catalog["ExternalUrls"] = [];
        $catalog["GenreItems"] = [];
        $catalog["People"] = [];
        $catalog["ProviderIds"] = [];
        $catalog["RemoteTrailers"] = [];
        $catalog["Studios"] = [];
        $catalog["Taglines"] = [];
        $catalog["Tags"] = [];
        $catalog["SpecialFeatureCount"] = 0;
        $catalog["LockedFields"] = [];
        $catalog["ChildCount"] = $childCount;
        $catalog["UserData"] = [
            "PlaybackPositionTicks" => 0,
            "PlayCount" => 0,
            "IsFavorite" => false,
            "Played" => false,
            "Key" => md5("_discover"),
            "ItemId" => "00000000000000000000000000000000"
        ];
        return $catalog;
    }

    public static function getCatalogsItems(array $itemData, array $query = []){
        $lang = app()->getLocale();
        $catalogs = self::getCatalogs();
        return Cache::remember('catalogs_'.md5($lang.json_encode($catalogs).json_encode($itemData).json_encode($query)), Carbon::now()->addMinutes(30), function() use ($catalogs, $itemData, $query){
            $catalog = @$itemData['catalog'];
            try {
                $folders = $catalogs;
                if (isset($catalog)) {
                    $parentKey = $catalog . '.folders';
                    $folders = data_get($catalogs, $parentKey);
                    $params = data_get($catalogs, $catalog . '.params');
                }

                $outcome = [];
                if (!empty($folders)) {
                    foreach ($folders as $key => $item) {
                        $folder = Folder::$CONFIG;
                        $folder['Name'] = t(ucwords($item['name']));
                        $folder['SortName'] = t(ucwords($item['name']));
                        $folder['Id'] = JellyfinItem::encodeId([
                            'type' => 'catalog',
                            'catalog' => isset($parentKey) ? $parentKey . '.' . $key : $key,
                            'catalog_name' => t(ucwords($item['name'])),
                            'title' => t(ucwords($item['name'])),
                        ]);
//                        $folder['ImageTags'] = [
//                            'Primary' => $folder['Id']
//                        ];
                        $folder['ArrayKey'] = isset($parentKey) ? $parentKey . '.' . $key : $key;
                        $folder['ServerId'] = jp_config('server_id');
                        //$folder['Type'] = 'Unknown';
                        $folder['PlayAccess'] = 'None';
                        $outcome[] = $folder;
                    }
                    return ['Items' => array_values($outcome), 'StartIndex' => 0, 'TotalRecordCount' => count($outcome)];
                }

                if (!empty($params))
                    return self::getItemsFromCatalog($params, $query);
            }catch (\Exception $e){}

            return ['Items' => [], 'StartIndex' => 0, 'TotalRecordCount' => 0];
        });
    }

    public static function getCatalogsItem(array $itemData){
        $folder = Folder::$CONFIG;
        $folder['ChildCount'] = 1;
        $folder['Name'] = t(ucfirst($itemData['catalog_name']));
        $folder['SortName'] = t($itemData['catalog_name']);
        $folder['Id'] = JellyfinItem::encodeId([
            'type' => 'catalog',
            'catalog' => $itemData['catalog'],
        ]);
        $folder['ArrayKey'] = $itemData['catalog'].'.folders';
        $folder['ServerId'] = jp_config('server_id');
        $folder['Type'] = 'Unknown';
        $folder['EnableMediaSourceDisplay'] = false;
        $folder['MediaType'] = "Unknown";
        $folder['PlayAccess'] = 'None';
        return $folder;
    }

    public static function getItemsFromCatalog(array $params, array $query = []){
        $limit = (int) @$query['Limit'] ?? 100;
        $startIndex = (int) @$query['StartIndex'] ?? 0;

        $outcome = [];
        $items = [];

        $addon = AddonsApiManager::getAddonById($params['addon_id']);
        try {
            if(!isset($params['query']['skip']) && !empty($params['has_skip']))
                $params['query']['skip'] = $startIndex;

            $api = new AddonsApiManager($addon['repository']['endpoint']);
            $items = $api->getCatalog(@$params['type'], str_replace('+', '.', @$params['id']), @$params['query'] ?? []);
            if(!empty($items) && !empty($params['has_skip'])){
                while (count($items) <= $limit){
                    $query = array_merge(@$params['query'] ?? [], ['skip' => count($items)+$startIndex]);
                    $items2 = $api->getCatalog(@$params['type'], str_replace('+', '.',@$params['id']), $query);
                    if(empty($items2))
                        break;
                    $items = array_merge($items, $items2);
                }
            }
        }catch (\Exception $e){}

        $totalRecordCount = 0;
        if(!empty($items)) {
            $totalRecordCount = count($items)+(count($items) > 0 ? $startIndex : 0)+(count($items) >= $limit ? $limit : 0);
//            $items = array_filter(array_map(function($item){
//                return !empty(@$item['imdb_id']) ? $item : null;
//            }, $items));

            foreach ($items as $outcomeItem) {

                $md5 = md5($outcomeItem['id']);
                if(isset($outcomeItem['imdb_id']))
                    $md5 = md5($outcomeItem['imdb_id']);

                $item = Items::query()->where('item_md5', $md5)->first();
                if(isset($outcomeItem['moviedb_id']))
                    $item = Items::query()->where('item_tmdb_id', $outcomeItem['moviedb_id'])->first();
                if(isset($outcomeItem['imdb_id']))
                    $item = Items::query()->where('item_imdb_id', $outcomeItem['imdb_id'])->first();

                if (!isset($item)) {
                    $typeMap = ['movie' => 'movie', 'series' => 'tvSeries', 'tv' => 'liveTv'];
                    $type = @$typeMap[@$outcomeItem['type']];

                    if(isset($type)) {
                        $item = new Items();
                        $item->item_md5 = $md5;
                        $item->item_addon_id = $addon['repository']['id'];
                        $item->item_addon_meta_id = @$outcomeItem['id'];
                        $item->item_addon_meta_type = @$outcomeItem['type'];
                        $item->item_imdb_id = @$outcomeItem['imdb_id'];
                        $item->item_tmdb_id = @$outcomeItem['moviedb_id'];
                        $item->item_type = $type;
                        $item->item_title = @$outcomeItem['name'];
                        $item->item_original_title = @$outcomeItem['name'];
                        $item->item_description = @$outcomeItem['description'];
                        $item->item_year = isset($outcomeItem['year']) ? intval($outcomeItem['year']) : null;
                        $item->item_image_url = @$outcomeItem['poster'];
                        $item->item_image_md5 = @md5(@$outcomeItem['poster']);
                        $item->item_server_id = jp_config('server_id');

                        $item->save();
                    }
                }

                if(isset($item))
                    $outcome[$item->item_md5] = $item->getJellyfinListItem();
            }
        }

        return ['Items' => array_values($outcome), 'StartIndex' => $startIndex, 'TotalRecordCount' => $totalRecordCount];
    }

}
