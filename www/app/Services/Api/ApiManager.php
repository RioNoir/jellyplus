<?php

namespace App\Services\Api;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ApiManager
{
    protected $endpoint, $timeout = 20, $connect_timeout = 10, $default_headers = [];

    public static function call(string $uri, string $method = 'GET', array $data = [], array $headers = []) {
        $api = new self();
        return $api->apiCall($uri, $method, $data, $headers);
    }

    protected function apiCall(string $uri, string $method = 'GET', array|string $data = [], array $headers = []) {
        $response = new ApiResponse();
        $cli = new Client();
        $uri = !str_starts_with('/', $uri) ? $uri : '/' . $uri;
        $headers = array_merge($this->default_headers, $headers);
        $options = [
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connect_timeout,
            'headers' => $headers,
        ];
        if($method == 'POST' || $method == 'PUT') {
            $options['form_params'] = $data;
        }elseif($method == 'POST_BODY') {
            $method = 'POST';
            if(is_array($data)) {
                $options['body'] = json_encode($data);
            }else{
                $options['body'] = $data;
            }
        }elseif($method == 'POST_JSON') {
            $method = 'POST';
            $options['json'] = $data;
        }elseif($method == 'GET'){
            if(!empty($data))
                $uri = sprintf("%s?%s", $uri, http_build_query($data));
        }
        $url = isset($this->endpoint) ? $this->endpoint . $uri : $uri;

        //Proxy
        $host = parse_url($url, PHP_URL_HOST);
        if(!empty(jp_config('http_proxy.host')) && jp_config('http_proxy.enabled') && $host !== "localhost" && !local_ip($host) &&
            (empty(jp_config('http_proxy.excluded_domains')) || !str_contains_arr($host, jp_config('http_proxy.excluded_domains')))) {

            $options['proxy'] = "http://";
            if(!empty(jp_config('http_proxy.username')) && !empty(jp_config('http_proxy.password'))){
                $options['proxy'] .= jp_config('http_proxy.username').":".jp_config('http_proxy.password').'@';
            }
            $options['proxy'] .= jp_config('http_proxy.host');
            if(!empty(jp_config('http_proxy.port'))){
                $options['proxy'] .= ":".jp_config('http_proxy.port');
            }
        }

        $request = [
            'url' => $url,
            'method' => $method,
            'data' => $data,
            'options' => $options
        ];
        $response->setRequest($request);

        try {
            $r = $cli->request($method, $url, $options);
            $response->setResponse($r);
        }catch (\Exception $e){
            Log::error($e->getMessage(), [
                'uri' => $uri,
                'method' => $method,
                'response' => [
                    'message' => @$e->getMessage(),
                    'status' => @$e->getCode(),
                    //'body' => @$e->getResponse()->getBody()->getContents(),
                ],
                'request' => $data,
                'headers' => $headers,
            ]);
        }
        return $response;
    }

    protected function getRandomAgent(): string
    {
        $agent_version_rand = rand(0,99);
        $agent_random = array(
            '1' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:64.0) Gecko/201001'.$agent_version_rand.' Firefox/64.0',
            '2' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/536.'.$agent_version_rand.' (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/536.'.$agent_version_rand,
            '3' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.'.$agent_version_rand.' (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.'.$agent_version_rand.' Edge/17.17134',
            '4' => 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.'.$agent_version_rand.') like Gecko',
            '5' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.'.$agent_version_rand.' (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.'.$agent_version_rand,
            '6' => 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.'.$agent_version_rand.' (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.'.$agent_version_rand,
            '7' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_2) AppleWebKit/537.'.$agent_version_rand.' (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.'.$agent_version_rand,
            '8' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_2) AppleWebKit/605.1.'.$agent_version_rand.' (KHTML, like Gecko) Version/12.0.2 Safari/605.1.'.$agent_version_rand.''
        );
        return $agent_random[rand(1,8)];
    }

    public static function getStaticRandomAgent(){
        $api = new self();
        return $api->getRandomAgent();
    }

}
