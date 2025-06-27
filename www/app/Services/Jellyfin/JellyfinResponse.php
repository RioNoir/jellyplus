<?php

namespace App\Services\Jellyfin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class JellyfinResponse
{
    protected $url, $path, $pathInfo, $query, $queryString, $status = 400, $body, $content, $headers = [], $request, $response;

    public function __construct(Request $request){
        $this->request = $request;
        $this->path = $request->path();
        $this->pathInfo = $this->request->getPathInfo();
        $this->query = $request->query();
        $this->queryString = $this->request->getQueryString();
        $this->content = $this->request->getContent();

        $this->url = config('jellyfin.url') . $this->pathInfo . ($this->queryString ? "?$this->queryString" : "");
    }

    public function make(){
        try {
            $response = Http::withHeaders($this->request->headers->all())
                ->timeout(120)
                ->send($this->request->method(), $this->url, [
                    'body' => $this->getContent(),
                ]);
            $this->status = $response->status();
            $this->body = $response->body();
            $this->headers = $response->headers();
        }catch(\Exception $e){}
        return $this;
    }

    public function setContent($data){
        $this->content = $data;
        return $this;
    }

    public function getContent($asArray = false){
        $content = $this->content;
        if(is_string($content) && is_json($content))
            $content = @json_decode($content, true, JSON_UNESCAPED_UNICODE);
        if(!$asArray && is_array($content)) {
            if(!str_starts_with(trim($this->request->header('User-Agent')), 'Mozilla'))
                $content = array_filter_recursively($content);
            $content = json_encode($content, JSON_UNESCAPED_UNICODE);
        }
        return $content;
    }

    public function getBody($asArray = false){
        $body = $this->body;
        if(is_string($body) && is_json($body))
            $body = @json_decode($body, true, JSON_UNESCAPED_UNICODE);
        if(!$asArray && is_array($body)) {
            if(!str_starts_with(trim($this->request->header('User-Agent')), 'Mozilla'))
                $body = array_filter_recursively($body);
            $body = json_encode($body, JSON_UNESCAPED_UNICODE);
        }
        if($asArray && $this->status > 200)
            $body = [];
        return $body;
    }

    public function mergeBody(array $body){
        if(is_array($this->getBody(true)))
            $this->body = array_merge($this->getBody(true), $body);
        return $this;
    }

    public function setStatus($status){
        $this->status = $status;
        return $this;
    }

    public function setHeaders($headers){
        $this->headers = $headers;
        return $this;
    }

    public function setBody($body){
        $this->body = $body;
        return $this;
    }

    public function getRequest(){
        return $this->request;
    }

    public function getPath(){
        return $this->path;
    }

    public function getQuery(){
        return $this->query;
    }

    public function getStatus(){
        return $this->status;
    }

    public function getHeaders(){
        $headers = $this->headers;
        if(empty($headers)){
            $headers = [
                'Content-Type' => 'application/json; charset=utf-8',
            ];
        }
        return $headers;
    }

    public function getResponse(){
        $this->log();
        return response($this->getBody(), $this->status, $this->headers);
    }

    protected function log(){
        $path = jp_data_path('app/response');
        $file = 'response_'.Carbon::now()->format('Ymd').'.json';
        $filePath = $path.'/'.$file;
        $fileContent = [];

        if(!file_exists($path))
            mkdir($path, 0777, true);

        if(file_exists($filePath))
            $fileContent = json_decode(file_get_contents($filePath), true) ?? [];

        $fileContent[Carbon::now()->format('H:i:s:v:u')] = [
            'path' => $this->path,
            'query' => $this->query,
            'status' => $this->status,
            'headers' => $this->headers,
            'content' => $this->getContent(),
            'data' => $this->getContent(true),
            'body' => $this->getBody(),
            'json' => $this->getBody(true),
        ];

        file_put_contents($filePath, json_encode($fileContent, JSON_PRETTY_PRINT));
        return true;
    }

}
