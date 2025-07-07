<?php

namespace App\Services\MediaFlowProxy;

use App\Services\Api\ApiManager;
use Illuminate\Support\Facades\Request;

class MediaFlowProxyManager
{
    private $mfpUrl, $mfpApiPassword, $mfpRemote = false;
    private $url, $host, $file, $headers = [], $query = [];

    public function __construct(){
        $this->mfpUrl = app_url('/mfp');
        $this->mfpApiPassword = getenv('API_PASSWORD');
    }

    public function setUrl($url){
        $this->url = $url;
        $this->host = parse_url($url, PHP_URL_HOST);
        $this->file = pathinfo($url, PATHINFO_BASENAME);
        return $this;
    }

    public function setHeaders(array $headers){
        $this->headers = array_filter($headers);
        if(!isset($this->headers['h_user-agent']))
            $this->headers['h_user-agent'] = ApiManager::getStaticRandomAgent();
    }

    public function hasHeaders(){
        return !empty($this->headers);
    }

    public function setQuery(array $query){
        $this->query = $query;
    }

    public function useRemoteServer(string $url, string $password){
        if(str_ends_with($url, '/'))
            $url = substr($url, 0, -1);

        $this->mfpUrl = $url;
        $this->mfpApiPassword = $password;
        $this->mfpRemote = true;
        return $this;
    }

    public function isHealthy(){
        $url = $this->mfpUrl.'/health';
        $api = ApiManager::call($url);
        $response = $api->getResponse();
        if(@$response['status'] == 200 && @$response['response']['json']['status'] == "healthy")
            return true;
        return false;
    }

    protected function isSameOrigin(){
        $clientIP = get_client_ip();

        if(local_ip($clientIP))
            return true;

        if(!empty($clientIP)){
            $url = $this->mfpUrl.'/proxy/ip?api_password='.$this->mfpApiPassword;
            $api = ApiManager::call($url);
            $response = $api->getResponse();
            $serverIpAddress = @$response['response']['json']['ip'];
            if(@$response['status'] == 200 && !empty($serverIpAddress)){
                if($clientIP == $serverIpAddress)
                    return true;
            }
        }
        return false;
    }

    public function generateUrl(){
        if(!$this->isExcluded() && (!$this->isSameOrigin() || $this->hasHeaders()) && $this->isHealthy()){
            $endpoint = $this->getEndpoint();
            $url = $this->mfpUrl.$endpoint;

            $query = [
                'api_password' => $this->mfpApiPassword,
                'd' => $this->url,
            ];

            if(!empty($this->headers))
                $query = array_merge($query, $this->headers);

            return $url.'?'.http_build_query($query);
        }
        return $this->url;
    }

    protected function getEndpoint(){
        return '/proxy/stream';
//        if(!empty($this->file) && str_ends_with($this->file, '.m3u8')){
//            return '/proxy/hls/manifest.m3u8';
//        }
//        return '/proxy/stream';
    }

    protected function isExcluded(){
        if(empty(jp_config('mediaflowproxy.excluded_domains')) || !str_contains_arr($this->host, jp_config('mediaflowproxy.excluded_domains')))
            return false;
        return true;
    }
}
