<?php

namespace App\Services\Api;

use Psr\Http\Message\ResponseInterface;

class ApiResponse
{
    protected $request, $response;

    public function __construct(ResponseInterface $response = null){
        $this->response = $response;
    }

    public function getResponse(): array {
        return [
            'status' => $this->getStatusCode(),
            'request' => $this->getRequest(),
            'response' => [
                'body' => $this->getBody(),
                'json' => $this->getJson(),
                'headers' => $this->getHeaders(),
            ],
        ];
    }

    public function getRequest() : array|null {
        return $this->request;
    }

    public function setRequest(array $request): void {
        $this->request = $request;
    }

    public function setResponse(ResponseInterface $response): void {
        $this->response = $response;
    }

    public function getBody(): string {
        if(!empty($this->response)) {
            $body = $this->response->getBody();
            return $body->getContents();
        }
        return "";
    }

    public function getJson(bool $associative = true){
        if(!empty($this->response)) {
            $body = $this->response->getBody();
            try {
                return json_decode((string)$body, $associative);
            } catch (\Exception $e) {
            }
        }
        return null;
    }

    public function getStatusCode(): int {
        if(!empty($this->response))
            return $this->response->getStatusCode();
        return 404;
    }

    public function hasPositiveResponse() : bool{
        if($this->getStatusCode() >= 200 && $this->getStatusCode() < 300)
            return true;
        return false;
    }

    public function getHeaders(): array {
        if(!empty($this->response))
            return $this->response->getHeaders();
        return [];
    }
}
