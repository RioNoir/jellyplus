<?php

namespace App\Services\IMDB;

use App\Services\Api\ApiManager;
use App\Services\Api\ApiResponse;
use App\Services\Items\ItemsManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class IMDBApiManager extends ApiManager
{
    protected $apiKey;
    protected $endpoint = null;
    protected $types = [
        'movie',
        'tvSeries'
    ];

    public function __construct(){}

    public function search(string $searchTerm, string $type = null, int $limit = -1, bool $cache = true){
        if(Cache::has('imdb_search_'.md5($searchTerm.$type.$limit)) && $cache) {
            $searchResponse = Cache::get('imdb_search_' . md5($searchTerm . $type . $limit));
            if (!empty($searchResponse))
                return $searchResponse;
        }

        $searchResponse = [];

        if(!str_starts_with($searchTerm, "tt")) {
            $uri = str_replace('{search_term}', urlencode($searchTerm), jp_config('imdb.suggestions_url'));
            $response = Cache::remember('imdb_suggestions_' . md5(urlencode($searchTerm)), Carbon::now()->addDay(), function () use ($uri) {
                return $this->apiCall($uri, 'GET', ['includeVideos' => 0]);
            });
            if (!empty($response['d'])) {
                $outcome = array_filter(array_map(function ($item) {
                    if(@$item['qid'] == "tvMiniSeries")
                        $item['qid'] = "tvSeries";
                    return $item;
                }, $response['d']), function ($item) {
                    return in_array(@$item['qid'], $this->types);
                });

                if (isset($type) && in_array($type, $this->types)) {
                    $outcome = array_filter($outcome, function ($item) use ($type) {
                        return @$item['qid'] == $type;
                    });
                }

                if($limit > 0)
                    $outcome = array_values(array_slice($outcome, 0, $limit));
                if (!empty($outcome)) {
                    foreach ($outcome as $item) {
                        if (isset($item['id']) && trim($item['id']) !== "" &&
                            isset($item['qid']) && trim($item['qid']) !== "") {

                            $searchItem = Cache::get('imdb_item_'.md5($item['id']), []);
                            if (empty($searchItem)) {
                                $searchItem = [
                                    'id' => $item['id'],
                                    'imdb_id' => $item['id'],
                                    'title' => @$item['l'],
                                    'poster' => @$item['i']['imageUrl'],
                                    'type' => @$item['qid'],
                                    'year' => @$item['y'],
                                ];
                            }
                            $searchResponse[] = $searchItem;
                        }
                    }
                }
            }
        }else{
            $searchItem = $this->getTitleDetails($searchTerm);
            if(!empty($searchItem))
                $searchResponse[] = $searchItem;
        }

        Cache::put('imdb_search_'.md5($searchTerm.$type.$limit), $searchResponse, Carbon::now()->addDay());
        return $searchResponse;
    }

    public function getTitleDetails(string $imdbId){
        $apiKey = $this->getApiKey();
        $uri = str_replace('{api_key}', $apiKey, jp_config('imdb.api_url')).'/'.$imdbId.'.json';

        $response = Cache::remember('imdb_detail_' . md5($imdbId), Carbon::now()->addDay(), function () use ($uri, $imdbId) {
            return $this->apiCall($uri, 'GET');
        });

        if(empty($response)) //Se è vuota la risposta potrebbe essere non valida la key
            Cache::forget('imdb_apikey');

        if(!empty($response) && !empty(@$response['pageProps']['aboveTheFoldData']) && !empty(@$response['pageProps']['mainColumnData'])) {
            unset($response['pageProps']['mainColumnData']['primaryImage']);
            unset($response['pageProps']['mainColumnData']['ratingsSummary']);
            $titleData = array_merge($response['pageProps']['aboveTheFoldData'], $response['pageProps']['mainColumnData']);

            $genres = array_filter(array_map(function ($item) {
                return @$item['text'];
            }, @$titleData['genres']['genres'] ?? []), function ($item) {
                return isset($item) && trim($item) !== '';
            }) ?? [];

            $actors = array_filter(array_map(function ($item) {
                return ['name' => @$item['node']['name']['nameText']['text'], 'type' => 'Actor'];
            }, @$titleData['castPageTitle']['edges'] ?? []), function ($item) {
                return isset($item['name']) && trim($item['name']) !== '';
            }) ?? [];

            $tags = array_filter(array_map(function ($item) {
                return @$item['node']['text'];
            }, @$titleData['keywords']['edges'] ?? []), function ($item) {
                return isset($item) && trim($item) !== '';
            }) ?? [];

            $releaseDate = @$titleData['releaseDate']['year'].'-'.@$titleData['releaseDate']['month'].'-'.@$titleData['releaseDate']['day'];
            if(str_contains($releaseDate, '--'))
                $releaseDate = @$titleData['releaseDate']['year'].'-01-01';

            $productionStatus = @$titleData['productionStatus']['currentProductionStage']['text'];

            $type = @$titleData['titleType']['id'];
            if($type == "tvMiniSeries")
                $type = "tvSeries";

            $searchItem = [
                'id' => $imdbId,
                'imdb_id' => $imdbId,
                'type' => $type,
                'plot' => @$titleData['plot']['plotText']['plainText'],
                'outline' => @$titleData['plot']['plotText']['plainText'],
                'dateadded' => Carbon::now()->format('Y-m-d H:i:s'),
                'title' => @$titleData['titleText']['text'],
                'originaltitle' => @$titleData['originalTitleText']['text'],
                'rating' => @$titleData['ratingsSummary']['aggregateRating'],
                'year' => @$titleData['releaseYear']['year'],
                'premiered' => Carbon::parse($releaseDate)->format('Y-m-d'),
                'releasedate' => Carbon::parse($releaseDate)->format('Y-m-d'),
                'enddate' => null,
                'runtime' => @$titleData['runtime']['seconds'],
                'genre' => $genres,
                'status' => $productionStatus,
                'poster' => @$titleData['primaryImage']['url'],
                'art' => [
                    'poster' => @$titleData['primaryImage']['url'],
                ],
                'actor' => $actors,
                'tag' => $tags
            ];

            if(isset($titleData['episodes'])){
                $searchItem += [
                    'totalSeasons' => count(@$titleData['episodes']['seasons'] ?? []),
                    'totalEpisodes' => @$titleData['episodes']['totalEpisodes']['total'] ?? 0,
                ];
                $searchItem['seasons'] = $this->getTVSeriesSeasons($imdbId, $searchItem['totalSeasons'], $searchItem);
            }

            return $searchItem;
        }
        return [];
    }

    public function getTVSeriesSeasons(string $imdbId, int $seasonCount = 1, array $imdbData = []) : array {
        $apiKey = $this->getApiKey();
        $uri = str_replace('{api_key}', $apiKey, jp_config('imdb.api_url')).'/'.$imdbId. "/episodes.json";

        $outcome = [];
        for($season = 1; $season <= $seasonCount; $season++) {
            $response = Cache::remember('imdb_season_' . md5($imdbId.'-'.$season), Carbon::now()->addDay(), function () use ($uri, $imdbId, $season) {
                return $this->apiCall($uri, 'GET', ['season' => $season, 'tconst' => $imdbId]);
            });
            if(!empty($response) && !empty(@$response['pageProps']['contentData']['section'])) {
                $seasonData = $response['pageProps']['contentData']['section'];

                $outcome[$season] = array_map(function ($item) use($imdbId){
                    $releaseDate = @$item['year'] . '-01-01';
                    if(isset($item['releaseDate']['year']) && isset($item['releaseDate']['month']) && $item['releaseDate']['day'])
                        $releaseDate = $item['releaseDate']['year'].'-'.$item['releaseDate']['month'].'-'.$item['releaseDate']['day'];
                    return [
                        'imdb_id' => $item['id'],
                        'parent_imdb_id' => $imdbId,
                        'type' => @$item['type'],
                        'season' => @$item['season'],
                        'episode' => @$item['episode'],
                        'title' => @$item['titleText'],
                        'releasedate' => Carbon::parse($releaseDate)->format('Y-m-d'),
                        'year' =>  @$item['releaseYear'],
                        'poster' =>  @$item['image']['url'],
                        'art' => [
                            'poster' => @$item['image']['url'],
                        ],
                        'plot' =>  @$item['plot'],
                        'rating' => @$item['aggregateRating'],
                    ];
                }, @$seasonData['episodes']['items'] ?? []);
            }
        }

        //Fix per quelle serie tipo One Piece che hanno solo una stagione ma con tanti episodi
        if($seasonCount == 1 && isset($imdbData['totalEpisodes']) && count($outcome[$seasonCount]) !== (int) $imdbData['totalEpisodes']) {
            for ($i = 0; $i < $imdbData['totalEpisodes']; $i++) {
                if(!isset($outcome[$seasonCount][$i])){
                    $outcome[$seasonCount][$i] = [
                        'parent_imdb_id' => $imdbId,
                        'type' => 'tvEpisode',
                        'season' => $seasonCount,
                        'episode' => $i
                    ];
                }
            }
        }

        return $outcome;
    }

    public function getApiKey(){
        if(Cache::has('imdb_apikey'))
            $this->apiKey = Cache::get('imdb_apikey');
        if(empty($this->apiKey)) {
            $html = $this->apiCall(jp_config('imdb.url'), 'GET', [], ['referer' => 'https://www.google.com/'], true);
            if (!empty($html)) {
                $crawler = new Crawler($html);
                $script = $crawler->filterXPath('//script[contains(@src, "/_buildManifest.js")]')->attr('src');
                if (!empty($script)) {
                    $this->apiKey = Str::between($script, '/_next/static/', '/_buildManifest.js');
                    if (!empty($this->apiKey))
                        Cache::put('imdb_apikey', $this->apiKey, Carbon::now()->addHour());
                }
            }
        }
        return $this->apiKey;
    }

    protected function apiCall(string $uri, string $method = 'GET', string|array $data = [], array $headers = [], $returnBody = false) {
        $default_headers = [
            'referer' => jp_config('imdb.url').'/',
            'user-agent' => $this->getRandomAgent(),
            'accept' => 'application/json, text/plain, */*',
            //'accept-language' => 'it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
            'cache-control' => 'no-cache',
            'pragma' => 'no-cache',
            'priority' => 'u=1, i',
            'x-nextjs-data' => '1'
        ];
        $headers = array_merge($default_headers, $headers);
        $response = parent::apiCall($uri, $method, $data, $headers);
        if($response->hasPositiveResponse()) {
            if($returnBody)
                return $response->getBody();
            return $response->getJson();
        }
        return [];
    }
}
