<?php

namespace Ometra\AetherClient;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AetherClient
{
	protected string $aether_url;
	protected string $uri_realm;
	protected string $token;
	protected string $log_level;

	public function __construct()
	{
		$this->aether_url = config('aether-client.base_url');
		$this->uri_realm  = config('aether-client.realm_id');
		$this->token   = config('aether-client.token');
		$this->log_level = strtolower(config('aether-client.log_level', 'error'));
	}

	public function report(string $action, ?string $status = null): array|null

	{
		$payload = ['action' => $action];
		if (!empty($status)) {
			$payload['status'] = $status;
		}

		$url = "{$this->aether_url}/realms/{$this->uri_realm}";
		$response = Http::withToken($this->token)
			->withHeaders(['Accept' => 'application/json'])
			->post($url, $payload);

		if ($status === 'ok') {
			if ($this->log_level === 'debug') {
				Log::channel('aether')->info(
					"Aether: Application ok -> {$action} | Payload: " . json_encode($payload, JSON_UNESCAPED_UNICODE)
				);
			}
		} elseif ($status === 'error') {
			if (in_array($this->log_level, ['error', 'debug'])) {
				Log::channel('aether')->error(
					"Aether: Application error -> {$action} | Status: {$response->status()} | Detail: {$response->body()} | Payload: " . json_encode($payload, JSON_UNESCAPED_UNICODE)
				);
			}
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
