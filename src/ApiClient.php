<?php

namespace Ometra\AetherClient;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiClient
{
    protected string $base_url;
    protected string $realm_id;
    protected string $log_level;
    protected ?string $token;

    public function __construct()
    {
        $this->base_url = config('aether-client.base_url');
        $this->realm_id = config('aether-client.realm_id');
        $this->log_level = strtolower(config('aether-client.log_level', 'error'));
        $this->token = config('aether-client.token', null);
    }

    private function request(string $method, string $endpoint, array $data = []): array|bool
    {
        $url = "{$this->base_url}{$endpoint}";

        $http = Http::withToken($this->token)->withHeaders(['Accept' => 'application/json']);

        $response = $http->{$method}($url, $data);

        if ($response->successful()) {
            $this->logSuccess($method, $endpoint, $data);
            return $response->json() ?? true;
        }

        $this->logError($method, $endpoint, $data, $response);
        return false;
    }

    private function logError(string $method, string $endpoint, array $data, $response)
    {
        if (in_array($this->log_level, ['error', 'debug', 'info'])) {
            Log::channel('aether')->error(
                "Aether API {$method} request failed -> {$endpoint} | Status: {$response->status()} | Detail: {$response->body()} | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE)
            );
        }
    }

    private function logSuccess(string $method, string $endpoint, array $data)
    {
        if ($this->log_level === 'debug') {
            Log::channel('aether')->debug(
                "Aether API {$method} request successful -> {$endpoint} | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE)
            );
        }
    }

    public function getRealmId(): string
    {
        return $this->realm_id;
    }

    public function getUriApplication(): ?string
    {
        $decode = base64_decode($this->token);
        $parts = explode(':', $decode);
        return $parts[0] ?? null;
    }

    public function get(string $endpoint, array $data = [])
    {
        return $this->request('get', $endpoint, $data);
    }

    public function post(string $endpoint, array $data = [])
    {
        return $this->request('post', $endpoint, $data);
    }

    public function put(string $endpoint, array $data = [])
    {
        return $this->request('put', $endpoint, $data);
    }

    public function delete(string $endpoint)
    {
        return $this->request('delete', $endpoint, []);
    }
}
