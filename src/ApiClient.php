<?php

namespace Ometra\AetherClient;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiClient
{

    protected string $base_url;
    protected string $realm_id;
    protected string $log_level;
    protected string $token;

    public function __construct()
    {
        $this->base_url = config('aether-client.base_url');
        $this->realm_id = config('aether-client.realm_id');
        $this->log_level = strtolower(config('aether-client.log_level', 'error'));
        $this->token = config('aether-client.token', null);
    }


    private function request(string $method, string $endpoint, array $params = [], array $data = []): array|null
    {
        $url = "{$this->base_url}{$endpoint}";

        $http = $http = Http::withToken($this->token)->withHeaders(['Accept' => 'application/json']);

        if (in_array($method, ['POST', 'PUT'])) {
            $response = $http->{$method}($url);
        } else {
            $response = $http->{$method}($url, $params);
        }

        if ($response->ok()) {
            $this->logSucces($method, $endpoint, $data);
            $json = $response->json();
            return $json['data'] ?? null;
        } else {
            $this->logError($method, $endpoint, $data, $response);
            return null;
        }

        $this->logFailHttp($method, $endpoint, $data, $response);
    }


    private function logError(string $method, string $endpoint, array $data = [], $response)
    {
        if (in_array($this->log_level, ['error', 'debug', 'info'])) {
            Log::channel('aether')->error(
                "Aether API {$method} request failed -> {$endpoint} | Status: {$response->status()} | Detail: {$response->body()} | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE)
            );
        }
    }

    private function logSucces(string $method, string $endpoint, array $data = [])
    {
        if ($this->log_level === 'debug') {
            Log::channel('aether')->debug(
                "Aether API {$method} request successful -> {$endpoint} | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE)
            );
        }
    }

    private function logFailHttp(string $method, string $endpoint, array $data = [], $response)
    {
        if (in_array($this->log_level, ['error', 'debug', 'info'])) {
            Log::channel('aether')->error(
                "Aether API FAIL to {$method} -> {$endpoint} | Status: {$response->status()} | Detail: {$response->body()} | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE)
            );
        }
    }

    public function getUriApplication():?string
    {
        $decode = base64_decode($this->token);
        $parts = explode(':', $decode);
        return $parts[0] ?? null;
    }

    public function getRealmId():?string
    {
        return $this->realm_id;
    }

    public function get(string $endpoint)
    {
        return $this->request('GET', $endpoint);
    }

    public function post(string $endpoint, array $data = [])
    {
        return $this->request('POST', $endpoint, $data);
    }

    public function put(string $endpoint, array $data = [])
    {
        return $this->request('PUT', $endpoint, $data);
    }

    public function delete(string $endpoint)
    {
        return $this->request('DELETE', $endpoint);
    }
}
