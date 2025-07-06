<?php

namespace App\Services\Jellyfin;

use App\Jobs\CommandExecutionJob;
use App\Models\Items;
use App\Services\Addons\CatalogsManager;
use App\Services\Jellyfin\lib\MediaSource;
use App\Services\Streams\StreamCollection;
use App\Services\Streams\StreamsManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class JellyfinItem
{
    protected $itemId, $item = [], $localItem = null, $itemData, $query, $response;

    public function __construct(string $itemId, array $query = [], JellyfinResponse $response = null){
        $this->itemData = self::decodeId($itemId);
        $this->itemId = $this->itemData['itemId'];
        $this->query = $query;
        $this->response = $response;
    }

    public static function findById(string $itemId, array $query = [], JellyfinResponse $response = null){
        return new self($itemId, $query, $response);
    }

    protected function findByApi($itemId){
        $apiKey = null;
        if(isset($this->query['apiKey']))
            $apiKey = $this->query['apiKey'];
        if(isset($this->query['api_key']))
            $apiKey = $this->query['api_key'];

        $api = new JellyfinApiManager();
        if(isset($apiKey))
            $api->setAuthenticationByApiKey($apiKey);
        if(!empty($this->query)){
            $detail = $api->getItemFromQuery($itemId, $this->query);
        }else{
            $detail = $api->getItem($itemId);
        }
        return $detail;
    }

    public static function encodeId(array $data){
        return base64_encode(json_encode($data));
    }

    public static function decodeId($itemId){
        if (is_base64($itemId)) { //default is md5
            $outcome = json_decode(base64_decode($itemId) ,true);
            if(!isset($outcome['itemId']))
                $outcome['itemId'] = $itemId;
            if(!isset($outcome['mediaSourceId']))
                $outcome['mediaSourceId'] = @$outcome['streamId'];
            return $outcome;
        }
        return ['itemId' => $itemId, 'mediaSourceId' => $itemId];
    }

    public function getJellyfinItem(){
        $item = [];
        if(isset($this->response))
            $item = $this->response->make()->getBody(true);
        if(empty($item))
            $item = $this->findByApi($this->itemId);

        if(!empty($item) && isset($item['Path']))
            $this->localItem = $this->getLocalItemByPath($item['Path']);

        if(!empty($item) && !empty(@$item['Overview'])){
            preg_match_all('/\[([^\]]+)\]/', $item['Overview'], $matches);
            if(!empty($matches[1])){
                dd($matches[1]);

            }
        }

        return $item;
    }

    public function getResponse(bool $skipMediaSources = false, bool $updateDBItem = true){
        $response = [];
        if($this->isDetailItem()){
            $response = $this->getDetailItem();
        } elseif ($this->isCatalogColletion()) {
            $response = CatalogsManager::getCatalogCollection();
        } elseif ($this->isCatalogFolder()){
            $response = CatalogsManager::getCatalogsItem($this->itemData);
        } else {
            //Jellyfin item
            $this->item = $this->getJellyfinItem();
            if(!empty($this->item)){
                $this->getItemExternalIds();
                if($this->isMovie() || $this->isEpisode()) {
                    $this->updateStrm();
                    if(!$skipMediaSources)
                        $this->getMediaSources();
                }
                if($updateDBItem && $this->isMovie() || $this->isSeries())
                    $this->updateDBItem();

                $response = $this->item;
            }
        }
        return $response;
    }

    public function getPath(){
        return $this->item['Path'];
    }

    public function getItemExternalIds(){
        $metaId = @$this->localItem->item_addon_meta_id;
        $imdbId = @$this->item['ProviderIds']['Imdb'];
        $tmdbId = @$this->item['ProviderIds']['Tmdb'];
        $kitsuId = @$this->item['ProviderIds']['Kitsu'];

        $JPStreamId = @$this->item['ProviderIds']['JPStream'];
        $JPStreamUrl = @$this->item['ProviderIds']['JPStreamUrl'];

        if (isset($this->item['SeriesId']) && isset($this->item['SeasonId']) && isset($this->item['IndexNumber'])) {
            $imdbId = null;
            $tmdbId = null;
            $kitsuId = null;

            $defaultEpisodeNumber = @$this->item['IndexNumber'];
            $episodeNumber = $this->getEpisodeNumber();
            $seasonNumber = $this->getSeasonNumber();

            $season = $this->getParentSeason();
            if(empty($JPStreamUrl) && !empty(@$season['ProviderIds']['JPStreamUrl']))
                $JPStreamUrl = parse_stream_url($season['ProviderIds']['JPStreamUrl'], $seasonNumber, $episodeNumber, $defaultEpisodeNumber);

            $series = $this->getParentSeries();
            if (!empty($series) && isset($seasonNumber) && isset($episodeNumber)) {
                if(isset($metaId))
                    $metaId = $metaId . ':' . $seasonNumber . ':' . $episodeNumber;
                if(!empty(@$series['ProviderIds']['Imdb']))
                    $imdbId = @$series['ProviderIds']['Imdb'] . ':' . $seasonNumber . ':' . $episodeNumber;
                if(!empty(@$series['ProviderIds']['Tmdb']))
                    $tmdbId = @$series['ProviderIds']['Tmdb'] . ':' . $seasonNumber . ':' . $episodeNumber;
                if(!empty(@$series['ProviderIds']['Kitsu']))
                    $kitsuId = @$series['ProviderIds']['Kitsu'] . ':' . $seasonNumber . ':' . $episodeNumber;

                if(empty($JPStreamUrl) && !empty(@$series['ProviderIds']['JPStreamUrl'])){
                    $JPStreamUrl = parse_stream_url($series['ProviderIds']['JPStreamUrl'], $seasonNumber, $episodeNumber, $defaultEpisodeNumber);
                }
            }
        }

        if(isset($this->localItem)) {
            if($this->isMovie() || $this->isSeries()) {
                $this->item['ProviderIds']['JP'] = @$this->localItem->item_md5;
                $this->item['ExternalUrls'][] = [
                    'Name' => 'Jellyplus',
                    'Url' => app_url('/web/#/configurationpage?name=JP_ITEM&itemId=' . @$this->localItem->item_md5.'&action=edit')
                ];
            }
            if($this->isMovie() || $this->isEpisode()){
                $this->item['ExternalUrls'][] = [
                    'Name' => 'Jellyplus Download',
                    'Url' => app_url('/web/#/configurationpage?name=JP_ITEM&itemId=' . @$this->localItem->item_md5. '&jItemId='.$this->itemId.'&action=download')
                ];
            }
        }

        $this->item['MetaIds']['itemId'] = @$this->localItem->item_md5;
        $this->item['MetaIds']['metaId'] = $metaId;
        if(isset($imdbId))
            $this->item['MetaIds']['metaId'] = $imdbId;

        $this->item['MetaIds']['imdbId'] = $imdbId;
        $this->item['MetaIds']['tmdbId'] = $tmdbId;
        $this->item['MetaIds']['kitsuId'] = $kitsuId;

        if($this->isMovie() || $this->isEpisode()) {
            if (!empty($JPStreamId)) {
                $this->item['MetaIds']['metaId'] = $JPStreamId;
                $this->item['MetaIds']['imdbId'] = $JPStreamId;
            }else{
                $this->item['ProviderIds']['JPStream'] = $this->item['MetaIds']['metaId'];
            }
            if (!empty($JPStreamUrl))
                $this->item['MetaIds']['metaUrl'] = $JPStreamUrl;
        }

        return $this->item['MetaIds'];
    }

    public function updateStrm(){
        //Update default stream url
        $strmFile = $this->getMainStrmFile();
        if(isset($strmFile)){
            $this->item['StrmFile'] = $strmFile;
            $this->item['StrmOldUrl'] = file_get_contents($strmFile);

            $source = '/stream';
            $sourceQuery = [
                'itemId' => $this->item['MetaIds']['itemId'],
                'metaId' => $this->item['MetaIds']['metaId'],
                'imdbId' => $this->item['MetaIds']['imdbId'],
            ];

            if(isset($this->item['MetaIds']['metaUrl']))
                $sourceQuery['url'] = $this->item['MetaIds']['metaUrl'];

            $fileUrl = @file_get_contents($strmFile);
            $currentSource = @parse_url($fileUrl);

            $currentSourceQuery = [];
            if(isset($currentSource['query']))
                parse_str($currentSource['query'], $currentSourceQuery);

            if(str_starts_with($fileUrl, app_url()) || (@$currentSource['path'] == $source && (
                        array_key_exists("imdbId", $currentSourceQuery) || array_key_exists("metaId", $currentSourceQuery)))){
                $sourceQuery = array_merge($currentSourceQuery, $sourceQuery);
            }else{
                $sourceQuery['url'] = $fileUrl;
            }

            if (isset($this->itemData['streamId'])) {
                $sourceQuery['streamId'] = $this->itemData['streamId'];
                unset($sourceQuery['url']);
            }
            if (isset($this->query['userId']))
                $sourceQuery['userId'] = $this->query['userId'];

            if(!isset($sourceQuery['metaType']))
                $sourceQuery['metaType'] = @$this->localItem->item_type;

            if(isset($sourceQuery['url']))
                $this->item['ProviderIds']['JPStreamUrl'] = $sourceQuery['url'];

            $sourceQuery['apiKey'] = jp_config('api_key');
            $sourceUrl = app_url($source).'?'.http_build_query($sourceQuery);
            file_put_contents($strmFile, $sourceUrl);

            $this->item['StrmUrl'] = $sourceUrl;
            if($this->isMovie() || $this->isEpisode()) {
                $this->item['ExternalUrls'][] = [
                    'Name' => 'Jellyplus Stream URL',
                    'Url' => $sourceUrl
                ];
            }
        }
        return $this;
    }

    public function updateDBItem(){
        $item = $this->localItem;
        if (isset($item)) {
            $api = new JellyfinApiManager();
            $api->setAuthenticationByApiKey();

            if (!isset($item->item_jellyfin_id) && in_array($item->item_type, ['movie', 'tvSeries'])) {
                $api->refreshItemMetadata($this->itemId);
                dispatch(new CommandExecutionJob('library:playback-info', ['--itemId' => $this->itemId]));
            }

//            $overview = "";
//            if(isset($item->item_user_id)) {
//                $user = $api->getUser($item->item_user_id);
//                if(!empty($user)) {
//                    $overview .= "[Added at ".$item->created_at." by ".$user['Name']."]\n\n";
//                }
//            }
//            $overview .= "[Updated at ".$item->updated_at."]\n\n";
//
//            if(!isset($this->item['Overview']) || empty($this->item['Overview']))
//                $this->item['Overview'] = $this->localItem->description;
//
//            $this->item['Overview'] = $overview . "\n\n" . @$this->item['Overview'];
//            if(isset($item->item_user_id)) {
//                $user = $api->getUser($item->item_user_id);
//                if(!empty($user)) {
//                    $this->item['ProviderIds']['SPCreatedBy'] = $user['Name'];
//                }
//            }

            $item->item_jellyfin_id = $this->itemId;
            $item->item_tmdb_id = $this->item['MetaIds']['tmdbId'];
            $item->save();

        }elseif(!empty($this->item)){
            $outcomeItem = $this->item;
            if(isset($outcomeItem['Id'])) {
                $typeMap = ['Movie' => 'movie', 'Series' => 'tvSeries'];
                $type = @$typeMap[@$outcomeItem['Type']];

                $path = get_item_path($outcomeItem['Path']);

                $itemMd5 = $outcomeItem['Id'];
                if (!empty(@$outcomeItem['ProviderIds']['Imdb']))
                    $itemMd5 = md5($outcomeItem['ProviderIds']['Imdb']);

                $item = Items::query()->where('item_md5', $itemMd5)
                    ->orWhere('item_jellyfin_id', $outcomeItem['Id'])
                    ->orWhere('item_path', $path);
                if(isset($outcomeItem['ProviderIds']['Imdb']))
                    $item->orWhere('item_imdb_id', $outcomeItem['ProviderIds']['Imdb']);
                $item = $item->first();

                if(!isset($item)) {
                    $item = new Items();
                    $item->item_md5 = $itemMd5;
                    $item->item_imdb_id = @$outcomeItem['ProviderIds']['Imdb'];
                    $item->item_tmdb_id = @$outcomeItem['ProviderIds']['Tmdb'];
                    $item->item_jellyfin_id = @$outcomeItem['Id'];
                    $item->item_type = $type;
                    $item->item_title = @$outcomeItem['Name'];
                    $item->item_original_title = @$outcomeItem['OriginalTitle'];
                    $item->item_year = isset($outcomeItem['ProductionYear']) ? intval($outcomeItem['ProductionYear']) : null;
                    $item->item_image_md5 = @$outcomeItem['ImageTags']['Primary'];
                    $item->item_server_id = jp_config('server_id');


                    if(!in_array(jp_data_path($path), [
                        jp_config('jellyfin.movies_path'),
                        jp_config('jellyfin.series_path'),
                        jp_config('jellyfin.tv_path'),
                    ])) {
                        $item->item_path = $path;
                        $item->save();
                    }
                }
            }
        }
    }

    public function getMediaSources(){
        try {
            set_time_limit(20);

            $itemId = $this->item['MetaIds']['itemId'];
            $metaId = $this->item['MetaIds']['metaId'];
            $userId = @$this->query['userId'];
            $mediaSourceId = @$this->itemData['streamId'];
            $mediaSources = @$this->item['MediaSources'] ?? [];
            $metaType = @$this->localItem->item_type;

            //Find streams by metaId
            Log::info('[item] Finding media sources for ' . $this->itemId . ' (' . $metaId . ')');
            if (isset($metaId)) {
                $now = Carbon::now()->format('YmdH');
                $key = md5($now. json_encode($mediaSources) . $itemId . $metaId . $metaType . $mediaSourceId . $userId. json_encode(jp_config()));
                $mediaSources = Cache::remember('media_sources_' . $key, Carbon::now()->addMinutes(10), function () use ($mediaSources, $itemId, $metaId, $metaType, $mediaSourceId, $userId) {
                    $streams = StreamCollection::findByMetaId($metaId, $metaType)
                        ->filterByFormats()
                        ->sortByOptions()
                        ->sortByKeywords();
                    if (!empty($streams)) {
                        foreach ($streams as $stream) {
                            $mediaSource = MediaSource::$CONFIG;
                            $mediaSource['UrlProtocol'] = $stream['stream_protocol'];
                            $mediaSource['Container'] = $stream['stream_container'];
                            $mediaSource['MediaSourceId'] = $stream['stream_md5'];
                            $mediaSource['ItemId'] = $this->itemId;
                            $mediaSource['Etag'] = $stream['stream_md5'];
                            $mediaSource['Id'] = self::encodeId([
                                'itemId' => $this->itemId,
                                'streamId' => $stream['stream_md5'],
                                'mediaSourceId' => $mediaSourceId,
                                'metaId' => $metaId,
                            ]);
                            $sourceQuery = [
                                'itemId' => $itemId,
                                'streamId' => @$stream['stream_md5'],
                                'metaId' => $metaId,
                                'metaType' => $metaType,
                                'userId' => $userId,
                                'apiKey' => jp_config('api_key'),
                            ];
                            $mediaSource['Path'] = app_url('/stream') . '?' . http_build_query($sourceQuery);
                            $mediaSource['Name'] = '[' . strtoupper($stream['stream_protocol']) . '] ' . $stream['stream_title'];
                            $mediaSource['SourceType'] = 'ExternalStream';
                            if (isset($stream['stream_watched_at']))
                                $mediaSource['LastPlayed'] = Carbon::parse($stream['stream_watched_at'])->utc()->format('Y-m-d\TH:i:s') . '.' . sprintf('%07d', 0) . 'Z';
                            $mediaSources[$stream['stream_md5']] = $mediaSource;
                        }
                    }
                    //Filter media sources
                    if (!empty($mediaSources)) {
                        if (isset($mediaSourceId)) {
                            $mediaSources = array_filter(array_map(function ($source) use ($mediaSourceId) {
                                if (isset($source['MediaSourceId']))
                                    return ($source['MediaSourceId'] == $mediaSourceId) ? $source : null;
                                return ($source['Id'] == $mediaSourceId) ? $source : null;
                            }, $mediaSources));
                        }
                        foreach ($mediaSources as $key => &$mediaSource) {
                            if(!isset($mediaSource['SourceType']) || $mediaSource['SourceType'] != 'ExternalStream') {
                                if ($mediaSource['Protocol'] == "Http") {
                                    $mediaSource['Name'] = "[HTTP] " . $mediaSource['Name'] . " (" . $mediaSource['Path'] . ")";
                                }
                                if ($mediaSource['Protocol'] == "File" && $mediaSource['Container'] !== "strm") {
                                    $mediaSource['Name'] = "[FILE] " . $mediaSource['Name'] . " (" . $mediaSource['Path'] . ")";
                                }
                                if (($mediaSource['Protocol'] == "Http" && $mediaSource['Id'] == $this->item['Id']) ||
                                    (isset($this->item['StrmFile']) && ($mediaSource['Path'] == $this->item['StrmFile'] ||
                                    $mediaSource['Path'] == $this->item['StrmOldUrl'] || $mediaSource['Path'] == $this->item['StrmUrl']))) {

                                    $mediaSource['Name'] = "[HTTP] " . jpt('stream_auto');
                                    $mediaSource['SourceType'] = 'ExternalStream';
                                }
                                if ($mediaSource['Container'] == "strm") {
                                    $mediaSource['Protocol'] = "Http";
                                    $mediaSource['Container'] = "hls";
                                }
                            }
                        }
                    }
                    return $mediaSources;
                });
            }
            Log::info('[item] Responding '.count($mediaSources).' media sources for ' . $this->itemId . ' (' . $metaId . ')');

            $this->item['MediaSources'] = array_values($mediaSources);
        }catch (\Exception $e){
            Log::error('[item] Unable to find media sources: '.$e->getMessage());
        }

        return $this;
    }

    public function getMainStrmFile()
    {
        if(str_ends_with($this->item['Path'], '.strm'))
            return $this->item['Path'];

        $filePath = pathinfo($this->item['Path'], PATHINFO_DIRNAME);
        $strmFiles = get_files_from_dir($filePath, ['strm']);
        if(!empty($strmFiles))
            return $strmFiles[array_key_first($strmFiles)];
        return null;
    }

    public function getDetailItem(){
        $item = Items::where('item_md5', $this->itemId)->first();
        if(isset($item))
            return $item->getJellyfinDetailItem();
        return [];
    }

    public function isDetailItem(){
        return Items::where('item_md5', $this->itemId)->exists();
    }

    public function getLocalItemById(string $itemId = null){
        if(!isset($itemId))
            $itemId = $this->itemId;

        $item = Items::where('item_jellyfin_id', $itemId)->first();
        if(isset($item))
            return $item;
        return null;
    }

    public function getLocalItemByPath(string $itemPath){
        $itemPath = get_item_path($itemPath);
        if(isset($itemPath)){
            $item = Items::where('item_path', $itemPath)->first();
            if(isset($item))
                return $item;
        }
        return null;
    }

    public function isCatalogColletion(){
        return $this->itemId == md5("_discover");
    }

    public function isCatalogFolder(){
        return isset($this->itemData['type']) && $this->itemData['type'] == "catalog";
    }

    public function isMovie(){
        return @$this->item['Type'] === 'Movie';
    }

    public function isSeries(){
        return @$this->item['Type'] === 'Series';
    }

    public function isEpisode(){
        return @$this->item['Type'] === 'Episode';
    }

    public function getParentSeries(){
        return $this->findByApi($this->item['SeriesId']);
    }

    public function getParentSeason(){
        return $this->findByApi($this->item['ParentId']);
    }

    public function getSeasonNumber(){
        $seasonNumber = @$this->item['ParentIndexNumber'];
        if((int) $seasonNumber <= 0){
            $api = new JellyfinApiManager();
            $seasons = $api->getSeasons($this->item['SeriesId']);
            if(!empty(@$seasons['Items'])){
                $seasonNumber = array_key_first(array_filter($seasons['Items'], function($item){
                        return $item['Id'] == @$this->item['ParentId'];
                    })) + 1;
            }
        }
        return $seasonNumber;
    }

    public function getEpisodeNumber(){
        $episodeNumber = @$this->item['IndexNumber'];
        if((int) $episodeNumber <= 0){
            $api = new JellyfinApiManager();
            $episodes = $api->getEpisodes($this->item['SeriesId'], ['seasonId' => $this->item['ParentId']]);
            if(!empty(@$episodes['Items'])){
                $episodeNumber = array_key_first(array_filter($episodes['Items'], function($item){
                        return $item['Id'] == @$this->item['Id'];
                    })) + 1;
            }
        }
        return $episodeNumber;
    }

}
