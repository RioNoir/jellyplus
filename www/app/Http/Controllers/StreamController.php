<?php

namespace App\Http\Controllers;

use App\Services\Api\ApiManager;
use App\Services\Jellyfin\JellyfinApiManager;
use App\Services\MediaFlowProxy\MediaFlowProxyManager;
use App\Services\Streams\StreamCollection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller as BaseController;

class StreamController extends BaseController
{
    public function getStream(Request $request){
        Log::info('[stream]['.get_client_ip().'] Requested stream "' . $request->fullUrl());

        $now = Carbon::now()->format('YmdH');
        $api = new JellyfinApiManager();

        $apiKey = $request->get('apiKey', null);
        $userId = $request->get('userId', null);
        $imdbId = $request->get('imdbId', null);
        $itemId = $request->get('itemId', null);
        $metaId = $request->get('metaId', null);
        $metaType = $request->get('metaType', null);

        if(!isset($metaId) && isset($imdbId))
            $metaId = $imdbId;

        $streamUrl = $request->get('url', null);
        $streamId = $request->get('streamId', null);
        $streamResolution = $request->get('streamResolution', jp_config('stream.resolution'));
        $streamFormat = $request->get('streamFormat', jp_config('stream.format'));
        $streamLang = $request->get('streamLang', null);
        $mediaFlowProxy = (bool) $request->get('mfp', true);
        $streamDownload = (bool) $request->get('download', false);
        $streamHeaders = $request->get('streamHeaders', []);

        $streamCacheKey = md5($now.$streamId.json_encode($request->all()).json_encode(jp_config()));

        if(!isset($apiKey) || !$api->testApiKey($apiKey))
            return response(null, 401);

        $api->setAuthenticationByApiKey($apiKey);

        if(!isset($metaId) && !isset($streamUrl))
            return response()->json(['error' => 'Please provide an Id or Url']);

        if(Cache::has('stream_url_'.$streamCacheKey))
            $streamUrl = Cache::get('stream_url_'.$streamCacheKey, $streamUrl);

        if(Cache::has('stream_headers_'.$streamCacheKey))
            $streamHeaders = Cache::get('stream_headers_'.$streamCacheKey, $streamHeaders);

        if(!isset($streamUrl)){
            if(!isset($streamLang))
                $streamLang = $api->getStreamingLanguageByUser($userId);

            Log::info('[stream]['.get_client_ip().'] Finding best stream with options: ' . $metaId . ', ' . $streamResolution . ', ' . $streamFormat . ', ' . $streamLang);

            //Find the best stream by id
            $streams = StreamCollection::findByMetaId($metaId, $metaType);
            if (isset($streamId)){
                $stream = $streams->sortByStreamId($streamId)->first();
            }else{
                $stream = $streams->filterByFormats()->sortByOptions($streamResolution, $streamFormat, $streamLang)
                    ->sortByKeywords()->firstByUrl();
            }

            //Return stream url
            if ($stream) {
                $path = $stream->getItemPath($itemId);
                if (isset($path)) {
                    $query = [
                        'itemId' => $itemId,
                        'streamId' => $stream->stream_md5,
                        'metaId' => $metaId,
                        'metaType' => $metaType,
                        'userId' => $userId,
                        'apiKey' => jp_config('api_key')
                    ];
                    file_put_contents($path, app_url('/stream') . '?' . http_build_query($query));
                }

                //$stream->stream_watched_at = Carbon::now();
                //$stream->save();

                $streamInfo = json_decode($stream->stream_info, true);
                $streamHeaders = @$streamInfo['behaviorHints']['proxyHeaders'] ?? [];
                if(empty($streamHeaders))
                    $streamHeaders = @$streamInfo['behaviorHints']['headers'] ?? [];

                $streamUrl = $stream->getStreamUrl(false);
                Log::info('[stream]['.get_client_ip().'] Requested stream "' . str_replace("\n", " ", $stream->stream_title) . '" from ' . $streamUrl);
            }
        }

        if(isset($streamUrl)){
            Cache::put('stream_url_'.$streamCacheKey, $streamUrl, Carbon::now()->addMinutes(10));
            Cache::put('stream_headers_'.$streamCacheKey, $streamHeaders, Carbon::now()->addMinutes(10));

            //Media Flow Proxy
            if($mediaFlowProxy && jp_config('mediaflowproxy.enabled')){
                $mfp = new MediaFlowProxyManager();
                $mfp->setUrl($streamUrl);

                if(jp_config('mediaflowproxy.enabled_external') && !empty(jp_config('mediaflowproxy.url')))
                    $mfp->useRemoteServer(jp_config('mediaflowproxy.url'), jp_config('mediaflowproxy.api_password'));

                if(isset($streamHeaders['request'])){
                    $headers = [];
                    $headers['h_referer'] = @$streamHeaders['request']['Referer'];
                    $headers['h_origin'] = @$streamHeaders['request']['Origin'];
                    $headers['h_user-agent'] = @$streamHeaders['request']['User-Agent'];
                    $mfp->setHeaders($headers);
                }elseif(!empty($streamHeaders)){
                    $headers = [];
                    $headers['h_referer'] = @$streamHeaders['Referer'];
                    $headers['h_origin'] = @$streamHeaders['Origin'];
                    $headers['h_user-agent'] = @$streamHeaders['User-Agent'];
                    $mfp->setHeaders($headers);
                }

                $streamUrl = $mfp->generateUrl();
            }

            Log::info('[stream]['.get_client_ip().'] Playing stream from ' . $streamUrl);

            if($streamDownload) {
                $streamFile = pathinfo($streamUrl, PATHINFO_BASENAME);
                Log::info('[download]['.get_client_ip().'] Downloading stream ('.$streamFile.') from ' . $streamUrl);
                return redirect($streamUrl, 301)->withHeaders([
                    'User-Agent' => ApiManager::getStaticRandomAgent(),
                    'Content-Disposition' => 'attachment; filename="' . $streamFile . '"'
                ]);
            }

            if(isset($streamHeaders['response'])) {
                $headers = $streamHeaders['response'];
                if(!isset($headers['User-Agent']) && !isset($headers['user-agent'])) {
                    $headers['User-Agent'] = ApiManager::getStaticRandomAgent();
                }
                return redirect($streamUrl, 301)->withHeaders($headers);
            }

            return redirect($streamUrl, 301)->withHeaders(['User-Agent' => ApiManager::getStaticRandomAgent()]);
        }

        return response(null, 404);
    }
}
