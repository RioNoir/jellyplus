<?php

namespace App\Http\Middleware;

use App\Jobs\CommandExecutionJob;
use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\Jellyfin\JellyfinManager;
use App\Services\Jellyfin\JellyfinResponse;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class JellyfinMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        //Call Jellyfin
        $request->attributes->set('jellyfin_response', new JellyfinResponse($request));

        $response = $next($request);
        $header = $request->header();

        $libraryRebuild = false;

        $protocol = "http";
        if($request->secure() || env('HTTP_X_FORWARDED_PROTO') == 'https' || env('HTTP_X_FORWARDED_SCHEME') == 'https')
            $protocol = "https";

        //Save app url
        $url = $protocol."://" . env('HTTP_HOST');
        if(md5(trim($url)) !== md5(trim(jp_config('url'))) && !str_contains(parse_url($url, PHP_URL_HOST), 'localhost')) {
            if(ping($url)) {
                jp_config('url', $url);
                $libraryRebuild = true;
            }
        }

        //Api key creation
        if(isset($header['authorization']) && isset($header['user-agent']) &&
            str_starts_with(@$header['user-agent'][0], 'Mozilla')) {
            //Api key creation
            if(empty(jp_config('api_key'))) {
                $api = new JellyfinApiManager($header);
                $apiKey = $api->createApiKeyIfNotExists(config('app.code_name'));
                if (isset($apiKey['AccessToken'])) {
                    jp_config('api_key', $apiKey['AccessToken']);
                    $libraryRebuild = true;
                }
            }

            //Setting lang
            $api = new JellyfinApiManager($header);
            $configuration = $api->getConfiguration();
            if(!empty($configuration) && isset($configuration['UICulture'])){
                if($configuration['UICulture'] !== jp_config('lang'))
                    jp_config('lang', $configuration['UICulture']);
            }
        }

        //Library rebuild
        if($libraryRebuild)
            dispatch(new CommandExecutionJob('library:rebuild'));

        //Response fixes
        if(!jellyfin_client($request)) { //Fix for Unofficial Clients
            if ($response instanceof JsonResponse) {
                $data = $response->getData(true);
                $transformedData = $this->transformEmptyArraysToNull($data);
                $response->setData($transformedData);
            }
        }

        return $response;
    }

    private function transformEmptyArraysToNull($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->transformEmptyArraysToNull($value);
            }
            return empty($data) ? null : $data;
        }
        return $data;
    }
}
