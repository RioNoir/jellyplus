<?php

namespace App\Services\Addons;

use App\Services\Api\ApiManager;
use App\Services\Jellyfin\JellyfinApiManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class AddonsApiManager extends ApiManager
{
    protected $timeout = 15, $connect_timeout = 5;

    public function __construct(string $endpoint = null){
        $this->timeout = jp_config('addons.timeout');
        $this->connect_timeout = jp_config('addons.connect_timeout');

        $this->endpoint = $endpoint;
    }

    public static function getAddonsFromPlugins(){
        $plugins = [];
        $addons = self::getAddons();
        foreach($addons as $addon){
            $plugins[] = [
                'CanUninstall' => false,
                //'ConfigurationFileName' => 'Jellyfin.Plugin.Test.xml',
                'Description' => $addon['manifest']['description'],
                'HasImage' => false,
                'Id' => md5($addon['manifest']['id']),
                'Name' => '<span style="color: #af39ae">[Addon]</span> ' .$addon['manifest']['name'],
                'Status' => 'Active',
                'Version' => $addon['manifest']['version'],
            ];
        }
        return $plugins;
    }

    public static function getAddonsFromPackages(){
        $packages = [];
        $addons = self::getAddons();
        foreach($addons as $addon){
            $packages[] = [
                'category' => "Jellyplus Addons",
                'description' => $addon['manifest']['description'],
                'guid' => md5($addon['manifest']['id']),
                'name' => $addon['manifest']['name'],
                'imageUrl' => @$addon['manifest']['background'] ?? @$addon['manifest']['logo'],
                'overview' => "",
                'owner' => $addon['manifest']['name'],
                'versions' => [
                    [
                        'VersionNumber' => $addon['manifest']['version'],
                        'changelog' => "",
                        'checksum' => md5($addon['manifest']['id']),
                        'repositoryName' => $addon['repository']['name'],
                        'repositoryUrl' =>  $addon['repository']['url'],
                        'sourceUrl' =>  $addon['repository']['url'],
                        'targetAbi' =>  $addon['manifest']['version'],
                        'timestamp' =>  Carbon::now()->timestamp,
                        'version' => $addon['manifest']['version'],
                    ]
                ]
            ];
        }
        return $packages;
    }

    public static function getAddons(){
        $addons = [];
        $api = new JellyfinApiManager();
        $api->setAuthenticationByApiKey();
        $repositories = array_filter($api->getPackagesRepositories() ?? [], function($repo){
            return $repo['Name'] !== "Jellyfin Stable";
        }) ?? [];

        $loadedAddons = [];
        foreach ($repositories as $repo) {
            $addon = Cache::remember('addon_'.md5($repo['Url']), Carbon::now()->addHours(24), function () use ($repo) {
                $response = self::call($repo['Url']);
                if (isset($response['id']) && isset($response['resources'])) {
                    $resources = array_filter(array_map(function($resource){
                        return (is_array($resource)) ? @$resource['name'] : $resource;
                    }, $response['resources']));

                    $types = [];
                    if(isset($response['types']) && is_array($response['types']))
                        $types = $response['types'];

                    $resourcesTypes = @array_merge(...array_filter(array_map(function ($resource) {
                        return (is_array($resource)) ? @$resource['types'] : null;
                    }, $response['resources']))) ?? [];

                    if(!empty($resourcesTypes))
                        $types = array_unique(array_merge($types, $resourcesTypes), SORT_REGULAR);

                    $prefixes = [];
                    if(isset($response['idPrefixes']) && is_array($response['idPrefixes']))
                        $prefixes = $response['idPrefixes'];

                    $resourcesPrefixes = @array_merge(...array_filter(array_map(function($resource){
                        return (is_array($resource)) ? @$resource['idPrefixes'] : null;
                    }, $response['resources']))) ?? [];

                    if(!empty($resourcesPrefixes))
                        $prefixes =  array_unique(array_merge($prefixes, $resourcesPrefixes), SORT_REGULAR);

                    $url = parse_url($repo['Url']);
                    $config = substr(str_replace('manifest.json', '', substr($url['path'], 1)), 0, -1);
                    $endpoint = $url['scheme'] . '://' . $url['host'] . (isset($url['port']) ? ':' . $url['port'] : '');
                    $repository = [
                        'id' => md5($repo['Url']),
                        'name' => $repo['Name'],
                        'url' => $endpoint,
                        'endpoint' => $endpoint . (!empty($config) ? '/'.$config : ""),
                        'host' => $url['host'],
                        'config' => $config,
                        'manifest' => $repo['Url'],
                        'resources' => $resources,
                        'types' => $types,
                        'prefixes' => $prefixes
                    ];
                    return [
                        'repository' => $repository,
                        'manifest' => $response,
                    ];
                }
                return null;
            });
            if(isset($addon))
                $addons[] = $addon;
        }

        if(!empty($addons)){
            foreach ($addons as $addon) {
                $loadedAddons[$addon['repository']['id']] = $addon['repository']['name'];
            }
        }

        if(md5(json_encode($loadedAddons)) !== md5(json_encode(jp_config('addons.loaded') ?? [])))
            jp_config('addons.loaded', $loadedAddons);

        return $addons;
    }

    public static function getAddonsByResource(string $resource){
        return Cache::remember('addons_'.md5($resource.json_encode(jp_config('addons'))), Carbon::now()->addHour(), function () use ($resource) {
            $addons = array_filter(array_map(function ($addon) use ($resource) {
                return in_array($resource, $addon['repository']['resources']) ? $addon : null;
            }, self::getAddons()));

            $addons = array_filter($addons, function ($addon) {
                if (in_array($addon['repository']['id'], @jp_config('addons.disabled') ?? []))
                    return false;
                return true;
            });

            switch ($resource) {
                case "catalog":
                    $addons = array_filter($addons, function ($addon) {
                        if (in_array($addon['repository']['id'], @jp_config('addons.excluded_discover') ?? []))
                            return false;
                        return true;
                    });
                    break;
                case "stream":
                    $addons = array_filter($addons, function ($addon) {
                        if (in_array($addon['repository']['id'], @jp_config('addons.excluded_stream') ?? []))
                            return false;
                        return true;
                    });
                    break;
            }

            return $addons;
        });
    }

    public static function getAddonById(string $id){
        $addons = self::getAddons();
        return !empty($addons) ? @array_values(array_filter(array_map(function ($addon) use($id){
            return $addon['repository']['id'] == $id ? $addon : null;
        }, $addons)))[0] : [];
    }

    public static function getActiveAddonsIds(): array {
        $addons = self::getAddons();
        return !empty($addons) ? array_values(array_filter(array_map(function ($addon) {
            return $addon['repository']['id'];
        }, $addons))) : [];
    }

    public function getManifest(){
        return $this->apiCall('/manifest.json');
    }

    public function getTVChannelsList(string $genre = null){
        return $this->getCatalog('tv', 'tv_channels', ['genre' => $genre]);
    }

    public function getTVChannel(string $channelId){
        $response = $this->apiCall('/meta/tv/'.$channelId.'.json');
        return @$response['meta'];
    }

    public function getMovie(string $metaId){
        if(Cache::has('mm_movie_'.md5($this->endpoint.$metaId)))
            return Cache::get('mm_movie_'.md5($this->endpoint.$metaId));

        $response = $this->apiCall('/stream/movie/'.trim($metaId).'.json');
        if(!empty($response)){
            Cache::put('mm_movie_'.md5($this->endpoint.$metaId), @$response['streams'], Carbon::now()->addHour());
            return @$response['streams'];
        }

        return [];
    }

    public function getSeriesEpisode(string $metaId, string $season = null, string $episode = null){
        if(isset($season) && isset($episode))
            $metaId = $metaId.':'.$season.':'.$episode;

        if(Cache::has('mm_episode_'.md5($this->endpoint.$metaId)))
            return Cache::get('mm_episode_'.md5($this->endpoint.$metaId));

        $response = $this->apiCall('/stream/series/'.urlencode(trim($metaId)).'.json');
        if(!empty($response)){
            Cache::put('mm_episode_'.md5($this->endpoint.$metaId), @$response['streams'], Carbon::now()->addHour());
            return @$response['streams'];
        }

        return [];
    }

    public function getStreams(string $metaId, string $metaType){
        if(Cache::has('mm_stream_'.md5($this->endpoint.$metaId.$metaType)))
            return Cache::get('mm_stream_'.md5($this->endpoint.$metaId.$metaType));

        $response = $this->apiCall('/stream/'.$metaType.'/'.trim($metaId).'.json');
        if(!empty($response)){
            Cache::put('mm_stream_'.md5($this->endpoint.$metaId.$metaType), @$response['streams'], Carbon::now()->addHour());
            return @$response['streams'];
        }

        return [];
    }

    public function getMeta(string $type, string $id){
        $response = $this->apiCall('/meta/' . urlencode($type) . '/' . urlencode($id) . '.json');
        return @$response['meta'];
    }

    public function getCatalog(string $type, string $id, array $params = []){
        $uri = '/catalog/' . urlencode($type) . '/' . urlencode($id) . '.json';
        if(!empty(array_filter($params)))
            $uri = '/catalog/' . urlencode($type) . '/' . urlencode($id) . '/' . http_build_query($params) . '.json';
        $response = $this->apiCall($uri);
        return @$response['metas'];
    }

    public function getTitleDetails(string $itemAddonId, string $type, string $addonId = null, bool $globalFind = true): array {
        if(Cache::has('mm_title_details_'.md5($itemAddonId.$type.$addonId.$globalFind)))
            return Cache::get('mm_title_details_'.md5($itemAddonId.$type.$addonId.$globalFind));

        $itemData = [];

        if(isset($addonId)) {
            $addon = self::getAddonById($addonId);
            if (!empty($addon) && in_array('meta', $addon['repository']['resources'])) {
                $this->endpoint = $addon['repository']['endpoint'];
                $meta = $this->getMeta($type, $itemAddonId);
            }
        }

        if(empty($meta) && $globalFind){
            $addons = self::getAddonsByResource('meta');
            foreach ($addons as $addon){
                $this->endpoint = $addon['repository']['endpoint'];
                $meta = $this->getMeta($type, $itemAddonId);
                if(!empty($meta))
                    break;
            }
        }

        if(!empty($meta)){
            $typesMap = ['movie' => 'movie', 'series' => 'tvSeries', 'tv' => 'liveTv'];
            $type = @$typesMap[@$meta['type']];

            if(isset($type)) {
                $itemData = [
                    'id' => $meta['id'],
                    'tmdb_id' => @$meta['tmdb_id'],
                    'imdb_id' => @$meta['imdb_id'],
                    'type' => $type,
                    'plot' => @$meta['description'] ?? "",
                    'outline' => @$meta['description'] ?? "",
                    'dateadded' => Carbon::now()->format('Y-m-d H:i:s'),
                    'title' => @$meta['name'] ?? "",
                    'originaltitle' => @$meta['name'] ?? "",
                    'status' => @$meta['status'],
                    'poster' => @$meta['poster'],
                    'genres' => @$meta['genres'],
                    'art' => [
                        'poster' => @$meta['poster'],
                    ]
                ];

                if ($type == 'tvSeries') {
                    $seasons = [];
                    $episodes = @$meta['videos'] ?? [];

                    if (!empty($episodes)) {
                        foreach ($episodes as $episode) {
                            $seasons[$episode['season']][] = [
                                'id' => $episode['id'],
                                'parent_tmdb_id' => @$episode['tmdb_id'],
                                'parent_imdb_id' => @$episode['imdb_id'],
                                'type' => 'tvEpisode',
                                'season' => @$episode['season'],
                                'episode' => @$episode['episode'],
                                'title' => @$episode['title'] ?? (@$episode['name'] ?? ""),
                                'releasedate' => Carbon::parse(@$episode['released'])->format('Y-m-d'),
                                'poster' => @$episode['thumbnail'],
                                'art' => [
                                    'poster' => @$episode['thumbnail'],
                                ],
                                'plot' => @$episode['overview']
                            ];
                        }
                    }

                    $itemData['seasons'] = $seasons;
                    $itemData['totalSeasons'] = count($seasons);
                    $itemData['totalEpisodes'] = count($episodes);
                }
            }
        }

        if(!empty($itemData))
            Cache::put('mm_title_details_'.md5($itemAddonId.$type.$addonId.$globalFind), $itemData, Carbon::now()->addHour());

        return $itemData;
    }

    public function search(string $searchTerm, string $type = null, int $limit = 20, string $addonId = null, bool $cache = true): array {
        if(!isset($addonId)) {
            $addons = self::getAddonsByResource('catalog');
        }else{
            $addons = [self::getAddonById($addonId)];
        }

        $cacheKey = md5($searchTerm.$type.$limit.$addonId.json_encode($addons).json_encode(jp_config('addons.excluded_search') ?? []));

        if(Cache::has('addons_search_'.$cacheKey) && $cache) {
            $searchResponse = Cache::get('addons_search_'.$cacheKey);
            if (!empty($searchResponse))
                return $searchResponse;
        }

        $searchResponse = [];
        foreach ($addons as $addon){

            if(!empty(jp_config('addons.excluded_search')) && in_array($addon['repository']['id'], jp_config('addons.excluded_search')))
                continue;

            $this->endpoint = $addon['repository']['endpoint'];
            if(!empty(@$addon['manifest']['catalogs'])){
                foreach ($addon['manifest']['catalogs'] as $catalog){
                    if(isset($catalog['extra'])){
                        $hasSearch = collect($catalog['extra'])->where('name', 'search')->count();
                        if($hasSearch > 0) {
                            $uri = '/catalog/' . $catalog['type'] . '/' . $catalog['id'] . '/';
                            $uri .= 'search='.urlencode($searchTerm).'.json';
                            $response = $this->apiCall($uri);

                            if(!empty(@$response['metas'])){
                                foreach ($response['metas'] as $meta){
                                    $metaId = @$meta['id'];
                                    if(isset($meta['imdb_id']))
                                        $metaId = $meta['imdb_id'];

                                    $typesMap = ['movie' => 'movie', 'series' => 'tvSeries', 'tv' => 'liveTv'];
                                    $metaType = @$typesMap[@$meta['type']];
                                    if(isset($metaType)) {
                                        if(isset($type) && $metaType !== $type)
                                            continue;

                                        $year = @$meta['year'];
                                        if(!isset($year) && isset($meta['releaseInfo']))
                                            $year = $meta['releaseInfo'];

                                        $searchItem = [
                                            'id' => $metaId,
                                            'tmdb_id' => @$meta['tmdb_id'],
                                            'imdb_id' => @$meta['imdb_id'],
                                            'title' => $meta['name'],
                                            'poster' => $meta['poster'],
                                            'type' => $metaType,
                                            'year' => $year,
                                            'addon_meta_id' => $metaId,
                                            'addon_meta_type' => @$meta['type'],
                                            'addon_id' => @$addon['repository']['id'],
                                        ];

                                        if (!empty($searchItem))
                                            $searchResponse[] = $searchItem;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        Cache::put('addons_search_'.$cacheKey, $searchResponse, Carbon::now()->addDay());
        return $searchResponse;
    }

    protected function apiCall(string $uri, string $method = 'GET', array|string $data = [], array $headers = [], $returnBody = false)
    {
        $response = parent::apiCall($uri, $method, $data, $headers);
        if($response->hasPositiveResponse()) {
            if($returnBody)
                return $response->getBody();
            return $response->getJson();
        }
        return [];
    }

    public static function call(string $uri, string $method = 'GET', array|string $data = [], array $headers = [], $returnBody = false)
    {
        $response = parent::call($uri, $method, $data, $headers);
        if($response->hasPositiveResponse()) {
            if($returnBody)
                return $response->getBody();
            return $response->getJson();
        }
        return [];
    }

}
