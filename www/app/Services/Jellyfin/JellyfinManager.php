<?php

namespace App\Services\Jellyfin;

use App\Services\Addons\CatalogsManager;
use App\Services\Items\ItemsSearchManager;
use Illuminate\Support\Facades\Log;
use LaLit\Array2XML;

class JellyfinManager
{
    public static $typesMap = ['Movie' => 'movie', 'Series' => 'tvSeries'];

    /**
     * @throws \Exception
     */
    public static function getItemsFromSearchTerm(JellyfinResponse $response) : null|array {
        $query = $response->getQuery();
        $response = $response->make()->getBody(true);

        $searchTerm = trim(strtolower(@$query['searchTerm']));
        if(isset($query['SearchTerm']))
            $searchTerm = trim(strtolower($query['SearchTerm']));
        if(isset($query['NameStartsWith']))
            $searchTerm = trim(strtolower($query['NameStartsWith']));

        $itemTypes = @$query['includeItemTypes'] ?? [];
        if(isset($query['IncludeItemTypes']))
            $itemTypes = @$query['IncludeItemTypes'] ?? [];

        if(!empty($itemTypes) && !is_array($itemTypes))
            $itemTypes = explode(',', trim($itemTypes));

        if(empty($itemTypes))
            $itemTypes = ['Movie', 'Series'];

        $isMissing = (bool) @$query['isMissing'];
        $mediaTypes = (bool) @$query['mediaTypes'];

        //Search Items
        foreach ($itemTypes as $itemType) {
            if(!empty($searchTerm) && in_array($itemType, ["Movie", "Series"]) && !$isMissing && !$mediaTypes) {
                $search = new ItemsSearchManager($searchTerm, @self::$typesMap[$itemType]);
                $results = $search->search()->getResults();

                if(!empty($results)){
                    $jellyfinIds = [];
                    if (!empty($response['Items']))
                        $jellyfinIds = array_filter(array_map(function ($item) { return @$item['Id'] ?? null;}, $response['Items']));

                    foreach ($results as $result) {
                        if ((isset($result->item_jellyfin_id) && in_array($result->item_jellyfin_id, $jellyfinIds)))
                            continue;

                        $resultType = isset($query['searchTerm']) ? "CollectionFolder" : "Video";
                        $response['Items'][] = $result->getJellyfinListItem($resultType);
                    }
                }
            }
        }

        $response['TotalRecordCount'] = count(@$response['Items'] ?? []);

        //Search Catalogs
        if(isset($query['ParentId'])){
            $itemData = JellyfinItem::decodeId($query['ParentId']);

            if((isset($itemData['type']) && $itemData['type'] == 'catalog') || $itemData['itemId'] == md5("_discover"))
                $response = CatalogsManager::getCatalogsItems($itemData, $query);
        }

        return $response;
    }

    /**
     * @throws \Exception
     */
    public static function createStructure(string $directory, array $titleData): void {
        self::createNfoFile($directory, $titleData);
        if(!empty($titleData['seasons'])){
            self::createSeasonsStructure($directory, $titleData);
        }else{
            self::createStrmFile($directory, $titleData);
        }
    }


    /**
     * @throws \Exception
     */
    protected static function createSeasonsStructure(string $directory, array $titleData): void {
        foreach($titleData['seasons'] as $season => $episodes) {
            if(count($episodes) > 1) {
                $season = sprintf("%02d", $season);
                $seasonName = "Season " . $season;
                $seasonPath = $directory . "/" . $seasonName;
                $season = [
                    'type' => 'tvSeason',
                    'parent_imdb_id' => @$titleData['imdb_id'],
                    'parent_item_id' => @$titleData['item_id'],
                    'parent_meta_id' => @$titleData['meta_id'],
                    'parent_meta_type' => @$titleData['meta_type'],
                    'parent_folder_id' => @$titleData['folder_id'],
                    //'title' => "Season " . sprintf("%02d", $season),
                    'seasonnumber' => $season,
                ];
                self::createNfoFile($seasonPath, $season);

                if (!file_exists($seasonPath))
                    mkdir($seasonPath, 0777, true);

                foreach ($episodes as $episode) {
                    $episode['parent_item_id'] = @$titleData['item_id'];
                    $episode['parent_imdb_id'] = @$titleData['imdb_id'];
                    $episode['parent_folder_id'] = @$titleData['folder_id'];
                    $episode['parent_meta_id'] = @$titleData['meta_id'];
                    $episode['parent_meta_type'] = @$titleData['meta_type'];

                    self::createNfoFile($seasonPath, $episode);
                    self::createStrmFile($seasonPath, $episode);
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected static function createNfoFile(string $directory, array $titleData): ?string {
        $typeMap = ['movie' => 'movie', 'tvSeries' => 'tvshow', 'liveTv' => 'movie', 'tvSeason' => 'season', 'tvEpisode' => 'episodedetails'];
        if(in_array($titleData['type'], array_keys($typeMap))){
            $type = $typeMap[$titleData['type']];
            $filePath = $directory . "/" . $type . ".nfo";
            try {

                $titleData['plot'] = xml_sanitize_string($titleData['plot'] ?? "");
                $titleData['outline'] = xml_sanitize_string($titleData['outline'] ?? "");
                $titleData['title'] = xml_sanitize_string($titleData['title'] ?? "");
                $titleData['originaltitle'] = xml_sanitize_string($titleData['originaltitle'] ?? "");
                $titleData['sorttitle'] = strtolower(xml_sanitize_string($titleData['title']));

                if($type == "episodedetails") {
                    $season = sprintf("%02d", $titleData['season']);
                    $fileName = "Episode S".$season."E".sprintf("%02d", $titleData['episode']);
                    $filePath = $directory . "/" . $fileName . ".nfo";
                    $titleData['episodenumber'] = $titleData['episode'];
                    $titleData['sorttitle'] = sprintf("%03d", $titleData['season']).' - '.sprintf("%04d", $titleData['episode']).' - '.$titleData['sorttitle'];
                }

                if (!file_exists($directory))
                    mkdir($directory, 0777, true);

                unset($titleData['id']);
                unset($titleData['seasons']);
                unset($titleData['totalSeasons']);
                unset($titleData['totalEpisodes']);
                $titleData['lockdata'] = "false";

                if($type !== "season" && $type !== "episodedetails") {
                    $imagePath = $directory . "/folder.jpeg";
                    if (!file_exists($imagePath)) {
                        try {
                            save_image(@$titleData['poster'], $imagePath);
                        } catch (\Exception $e) {
                        }
                    }
                    if (file_exists($imagePath))
                        $titleData['art']['poster'] = $imagePath;
                }

                if (file_exists($filePath)) {
                    $xml = simplexml_load_string(file_get_contents($filePath), "SimpleXMLElement", LIBXML_NOCDATA);
                    $xmlData = json_decode(json_encode($xml), true);
                    if(is_array($xmlData) && !empty($xmlData))
                        $titleData = array_merge($titleData, $xmlData);
                }

                $xml = Array2XML::createXML($type, $titleData);
                file_put_contents($filePath, $xml->saveXML());

                return $filePath;
            }catch (\Exception $e){
                Log::error($e->getMessage());
            }
        }
        return null;
    }

    /**
     * @throws \Exception
     */
    public static function createStrmFile(string $directory, array $titleData): ?string {
        try {
            $source = '/stream';

            if ($titleData['type'] == "tvEpisode") {
                $season = sprintf("%02d", $titleData['season']);
                $fileName = "Episode S".$season."E".sprintf("%02d", $titleData['episode']);
                $filePath = $directory . "/" . $fileName . ".strm";

                $sourceQuery['itemId'] = @$titleData['parent_item_id'];
                $sourceQuery['metaId'] = @$titleData['parent_meta_id'].":".$titleData['season'].":".$titleData['episode'];
                $sourceQuery['metaType'] = @$titleData['parent_meta_type'];

                if(isset($titleData['parent_imdb_id']))
                    $sourceQuery['imdbId'] = $titleData['parent_imdb_id'].":".$titleData['season'].":".$titleData['episode'];
            }else{
                $filePath = $directory . "/" . @$titleData['file_id'] . ".strm";

                $sourceQuery['itemId'] = @$titleData['item_id'];
                $sourceQuery['metaId'] = @$titleData['meta_id'];
                $sourceQuery['metaType'] = @$titleData['meta_type'];

                if(isset($titleData['imdb_id']))
                    $sourceQuery['imdbId'] = $titleData['imdb_id'];
            }

            if(file_exists($filePath)){
                $fileUrl = file_get_contents($filePath);
                $currentSource = @parse_url($fileUrl);

                $currentSourceQuery = [];
                if(isset($currentSource['query']))
                    parse_str($currentSource['query'], $currentSourceQuery);

                if(str_starts_with($fileUrl, app_url()) || (@$currentSource['path'] == $source && (
                        array_key_exists("imdbId", $currentSourceQuery) || array_key_exists("metaId", $currentSourceQuery)))){
                    $sourceQuery = array_merge($sourceQuery, $currentSourceQuery);
                }else{
                    $sourceQuery['url'] = $fileUrl;
                }
            }

            $sourceQuery['streamId'] = null;
            $sourceQuery['expires'] = null;
            $sourceQuery['apiKey'] = jp_config('api_key');
            $sourceQuery = array_filter($sourceQuery);
            file_put_contents($filePath, app_url($source).'?'.http_build_query($sourceQuery));

            return $filePath;
        }catch (\Exception $e){}
        return null;
    }

}
