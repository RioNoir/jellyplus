<?php

namespace App\Services\Items;

use App\Models\Items;
use App\Services\Addons\AddonsApiManager;
use App\Services\IMDB\IMDBApiManager;
use App\Services\TMDB\TMDBApiManager;
use Carbon\Carbon;

class ItemsSearchManager
{
    protected $searchTerm, $itemType, $results = [];

    public function __construct(string $searchTerm, string $itemType = null){
        $this->searchTerm = trim($searchTerm);
        $this->itemType = $itemType;
    }

    /**
     * @throws \Exception
     */
    public function search(): static
    {
        if(!empty($this->searchTerm)){
            if(jp_config('tmdb.enabled')){
                $this->searchOnTmdb();
            }else{
                $this->searchOnImdb();
            }
            if(!(bool)jp_config('addons.search_disabled'))
                $this->searchOnAddons();
            if(empty($this->results))
                $this->searchOnLocal();
        }
        return $this;
    }

    public function getResults(): array{
        return $this->results;
    }

    /**
     * @throws \Exception
     */
    public function searchOnLocal(): ItemsSearchManager
    {
        $searchTerm = addslashes(str_replace("'", " ", $this->searchTerm));
        $query = Items::query()->where(function($query) use($searchTerm){
            $query->where('item_imdb_id', 'like', "%{$searchTerm}%")
                ->orWhere('item_title', 'like', "%{$searchTerm}%")
                ->orWhere('item_original_title', 'like', "%{$searchTerm}%")
                ->orWhereRaw("REPLACE(item_title, '-', ' ') LIKE '%{$searchTerm}%'")
                ->orWhereRaw("REPLACE(item_original_title, '-', ' ') LIKE '%{$searchTerm}%'")
                ->orWhereRaw("REPLACE(item_title, '-', '') LIKE '%{$searchTerm}%'")
                ->orWhereRaw("REPLACE(item_original_title, '-', '') LIKE '%{$searchTerm}%'");
            })->where('updated_at', '>=', Carbon::now()->subDay());
        if(isset($this->itemType))
            $query->where('item_type', $this->itemType);

        $results = [];
        if($query->count() > 0){
            foreach ($query->get() as $item){
                $results[$item->item_md5] = $item;
            }
        }
        $this->results = array_merge($this->results, $results);
        return $this;
    }

    public function searchOnImdb(): ItemsSearchManager
    {
        $api = new IMDBApiManager();
        $response = $api->search($this->searchTerm, $this->itemType);
        $results = [];
        foreach($response as $result){
            $results[md5($result['id'])] = ItemsManager::imdbDataToDatabase($result);
        }
        $this->results = array_merge($this->results, $results);
        return $this;
    }

    public function searchOnTmdb(): ItemsSearchManager
    {
        $api = new TMDBApiManager();
        $response = $api->search($this->searchTerm, $this->itemType, 10);
        $results = [];
        foreach($response as $result){
            $results[md5($result['id'])] = ItemsManager::imdbDataToDatabase($result);
        }
        $this->results = array_merge($this->results, $results);
        return $this;
    }

    public function searchOnAddons(): ItemsSearchManager
    {
        $api = new AddonsApiManager();
        $response = $api->search($this->searchTerm, $this->itemType, 10);
        $results = [];
        foreach($response as $result){
            $results[md5($result['id'])] = ItemsManager::imdbDataToDatabase($result);
        }
        $this->results = array_merge($this->results, $results);
        return $this;
    }
}
