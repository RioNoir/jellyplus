<?php

namespace App\Providers;

use CodeBugLab\Tmdb\Repository\AbstractRepository;
use CodeBugLab\Tmdb\Tmdb;
use CodeBugLab\Tmdb\Url\ApiGenerator;
use Illuminate\Support\ServiceProvider;

class TMDBServiceProvider extends ServiceProvider
{
    public function boot(){}

    public function register()
    {
        $this->app->bind(ApiGenerator::class, function () {
            $apiKey = (!empty(jp_config('tmdb.api_key')) ? jp_config('tmdb.api_key') : "");
            $apiLanguage = (!empty(jp_config('tmdb.api_language')) ? jp_config('tmdb.api_language') : "en");
            return new ApiGenerator(AbstractRepository::$apiUrl, $apiKey, $apiLanguage);
        });

        $this->app->bind('Tmdb', Tmdb::class);
    }
}
