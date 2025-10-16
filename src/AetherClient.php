<?php

namespace Ometra\AetherClient;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AetherClient
{
	protected string $aether_url;
	protected string $uri_realm;
	protected string $api_key;
	protected string $token;
	protected string $log_level;

	public function __construct()
	{
		$this->aether_url = config('aether-client.base_url');
		$this->uri_realm  = config('aether-client.realm_id');
		$this->api_key = config('aether-client.api_key');
		$this->token   = config('aether-client.token');
		$this->log_level = strtolower(config('aether-client.log_level', 'error'));
	}

	public function report(string $action, array|string|null $data = null, string $status = 'ok'): array|null
	{
		$payload = [
			'action' => $action,
			'data'   => $data,
			'status' => $status,
		];

		$url = "{$this->aether_url}/realms/{$this->uri_realm}";
		$response = Http::withToken($this->token)
			->withHeaders([
				'Accept' => 'application/json',
			])
			->post($url, $payload);

		if ($status === 'ok' && $this->log_level === 'debug') {
			Log::channel('aether')->info(
				"Aether: Application ok -> {$action} | Payload: " . json_encode($data, JSON_UNESCAPED_UNICODE)
			);
		} else {
			Log::channel('aether')->error(
				"Aether: Aplication error-> {$action} | Payload: " . json_encode($data, JSON_UNESCAPED_UNICODE)
			);
		}

		if ($response->ok()) {
			return $response->json();
		} else {
			Log::channel('aether')->alert(
				"Aether FAIL to report -> {$action} | HTTP Response: {$response->body()}"
			);

			return [
				'status' => 'error',
				'message' => $response->body(),
			];
		}
	}
}
