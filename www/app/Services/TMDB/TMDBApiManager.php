<?php

namespace App\Services\TMDB;

use App\Services\Api\ApiManager;
use App\Services\Api\ApiResponse;
use App\Services\Items\ItemsManager;
use Carbon\Carbon;
use CodeBugLab\Tmdb\Facades\Tmdb;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class TMDBApiManager
{
    protected $types = [
        'movie',
        'tvSeries'
    ];

    public function __construct(){}

    public function search(string $searchTerm, string $type = null, int $limit = -1, bool $cache = true){
        if(Cache::has('tmdb_search_'.md5($searchTerm.$type.$limit)) && $cache) {
            $searchResponse = Cache::get('tmdb_search_' . md5($searchTerm . $type . $limit));
            if (!empty($searchResponse))
                return $searchResponse;
        }

        $searchResponse = [];

        $response = Cache::remember('tmdb_suggestions_' . md5(urlencode($searchTerm).$type), Carbon::now()->addDay(), function () use ($type, $searchTerm) {
            $tmdb = Tmdb::search();
            switch ($type) {
                case 'movie':
                    $tmdb->movies();
                    break;
                case 'tvSeries':
                    $tmdb->tvShows();
                    break;
                default:
                    $tmdb->multi();
            }
            return $tmdb->query($searchTerm)->get();
        });

        if(!empty($response) && !empty(@$response['results'])) {
            $outcome = array_filter(array_map(function ($item) use($type){
                if(isset($item['media_type'])){
                    $item['type'] = "movie";
                    if(@$item['media_type'] == "tv")
                        $item['type'] = "tvSeries";
                }else{
                    $item['type'] = $type;
                }
                return $item;
            }, $response['results']), function ($item) {
                return in_array(@$item['type'], $this->types);
            });

            if($limit > 0)
                $outcome = array_values(array_slice($outcome, 0, $limit));

            if (!empty($outcome)) {
                foreach ($outcome as $item) {
                    if (isset($item['id']) && trim($item['id']) !== "" &&
                        isset($item['type']) && trim($item['type']) !== "") {

                        $searchItem = Cache::get('tmdb_item_' . md5($item['id']), []);
                        if (empty($searchItem)) {
                            $date = null;
                            $title = null;
                            switch ($item['type']) {
                                case 'tvSeries':
                                    $detail = Tmdb::tv();
                                    $date = @$item['first_air_date'];
                                    $title = @$item['name'];
                                    break;
                                default:
                                    $detail = Tmdb::movies();
                                    $date = @$item['release_date'];
                                    $title = @$item['title'];
                                    break;
                            }
                            $ids = $detail->externalIds($item['id'])->get();

                            $year = null;
                            if (isset($date))
                                $year = Carbon::parse($date)->year;

                            $poster = null;
                            if (isset($item['poster_path']))
                                $poster = jp_config('tmdb.poster_url') . $item['poster_path'];

                            if (isset($ids['imdb_id'])) {
                                $searchItem = [
                                    'id' => @$ids['imdb_id'],
                                    'tmdb_id' => @$item['id'],
                                    'imdb_id' => @$ids['imdb_id'],
                                    'title' => $title,
                                    'poster' => $poster,
                                    'type' => @$item['type'],
                                    'year' => $year,
                                ];
                            }
                        }

                        if (!empty($searchItem))
                            $searchResponse[] = $searchItem;
                    }
                }
            }
        }

        Cache::put('tmdb_search_'.md5($searchTerm.$type.$limit), $searchResponse, Carbon::now()->addDay());
        return $searchResponse;
    }

    public function getTitleDetails(string $tmdbId, string $type){
        $response = Cache::remember('tmdb_detail_' . md5(urlencode($tmdbId).$type), Carbon::now()->addDay(), function () use ($tmdbId, $type) {
            $detail = ($type == "tvSeries") ? Tmdb::tv() : Tmdb::movies();
            $response = $detail->details($tmdbId)->get();
            if (isset($response['id'])) {
                $response['type'] = $type;
                $response['ids'] = $detail->externalIds($tmdbId)->get();
                return $response;
            }
            return [];
        });

        if(!empty($response) && !empty(@$response['id']) && (!empty($response['imdb_id']) || !empty(@$response['ids']['imdb_id']))) {
            $imdbId = @$response['imdb_id'] ?? @$response['ids']['imdb_id'];

            $genres = array_filter(array_map(function ($item) {
                return @$item['name'];
            }, @$response['genres'] ?? []), function ($item) {
                return isset($item) && trim($item) !== '';
            }) ?? [];

            $actors = [];
            $tags = [];

            $endDate = null;
            $releaseDate = @$response['release_date'] ?? @$response['first_air_date'];
            if(@$response['status'] == "Ended" && isset($response['last_air_date']))
                $endDate = Carbon::parse($response['last_air_date'])->format('Y-m-d');

            $poster = null;
            if(isset($response['poster_path']))
                $poster = jp_config('tmdb.poster_url') . $response['poster_path'];

            $searchItem = [
                'id' => $imdbId,
                'tmdb_id' => $response['id'],
                'imdb_id' => $imdbId,
                'type' => $response['type'],
                'plot' => @$response['overview'],
                'outline' => @$response['overview'],
                'dateadded' => Carbon::now()->format('Y-m-d H:i:s'),
                'title' => @$response['title'] ?? @$response['name'],
                'originaltitle' => @$response['original_title'] ?? @$response['original_name'],
                'rating' => @$response['vote_average'],
                'year' => Carbon::parse($releaseDate)->year,
                'premiered' => Carbon::parse($releaseDate)->format('Y-m-d'),
                'releasedate' => Carbon::parse($releaseDate)->format('Y-m-d'),
                'enddate' => $endDate,
                'runtime' => null,
                'genre' => $genres,
                'status' => @$response['status'],
                'poster' => $poster,
                'art' => [
                    'poster' => $poster,
                ],
                'actor' => $actors,
                'tag' => $tags
            ];


            if(isset($response['seasons'])){
                $searchItem += [
                    'totalSeasons' => @$response['number_of_seasons'],
                    'totalEpisodes' => @$response['number_of_episodes'],
                ];
                $searchItem['seasons'] = $this->getTVSeriesSeasons($tmdbId, $searchItem['totalSeasons'], $searchItem);
            }

            return $searchItem;
        }
        return [];
    }

    public function getTVSeriesSeasons(string $tmdbId, int $seasonCount = 1, array $tmdbData = []) : array {
        $outcome = [];
        for($season = 1; $season <= $seasonCount; $season++) {
            $response = Cache::remember('tmdb_season_' . md5($tmdbId.'-'.$season), Carbon::now()->addDay(), function () use ($tmdbId, $season) {
                return Tmdb::tvSeasons()->details($tmdbId, $season)->get();
            });

            if(!empty($response) && !empty(@$response['episodes'])) {
                $outcome[$season] = array_map(function ($item) use($tmdbId, $tmdbData){

                    $poster = null;
                    if(isset($item['still_path']))
                        $poster = jp_config('tmdb.image_url') . $item['still_path'];

                    return [
                        'tmdb_id' => $item['id'],
                        'parent_tmdb_id' => $tmdbData['tmdb_id'],
                        'parent_imdb_id' => $tmdbData['imdb_id'],
                        'type' => 'tvEpisode',
                        'season' => @$item['season_number'],
                        'episode' => @$item['episode_number'],
                        'title' => @$item['name'],
                        'releasedate' => Carbon::parse(@$item['air_date'])->format('Y-m-d'),
                        'year' =>  Carbon::parse(@$item['air_date'])->year,
                        'poster' =>  $poster,
                        'art' => [
                            'poster' => $poster,
                        ],
                        'plot' =>  @$item['overview'],
                        'rating' => @$item['vote_average'],
                    ];
                }, @$response['episodes'] ?? []);
            }
        }

        return $outcome;
    }
}
