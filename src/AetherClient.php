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

	public function report(string $action, array|string|null $data = null): array|null
	{
		$response = Http::withToken($this->token)->withHeaders([
			'Accept'    => 'application/json',
		])->post(
			$this->aether_url . '/realms/' . $this->uri_realm,
			[
				'action' => $action,
				'data'   => $data,
			]
		);

		if ($response->ok()) {
			Log::channel('aether')->info('Action reported -> ' . $action . ' Payload: ' . json_encode($data));
			return $response->json();
		} else {
			Log::channel('aether')->alert('Failed to report action -> ' . $action);
			return [
				'status' => 'error',
				'message' => $response->body(),
			];
		}
	}

	public function getBaseUrl(): string
	{
		return $this->aether_url;
	}

	public function getRealmId(): string
	{
		return $this->uri_realm;
	}

	public function getLogLevel(): string
	{
		return $this->log_level;
	}

	public function getToken(): string
	{
		return $this->token;
	}

	public function getUriApplication(): string|null
	{
		$decode = base64_decode($this->token);
		$uri_exploded = explode('?', $decode);
		return $uri_exploded[0] ?? null;
	}
}
