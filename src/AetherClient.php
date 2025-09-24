<?php

namespace Ometra\AetherClient;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AetherClient
{
	protected string $aether_url;
	protected string $uri_realm;
	protected string $api_key;

	public function __construct()
	{
		$this->aether_url = config('aether-client.base_url');
		$this->uri_realm  = config('aether-client.realm_id');
		$this->api_key = config('aether-client.api_key');
	}

	public function report(string $action, array|string|null $data = null): array|null
	{
		$response = Http::withHeaders([
			'X-Api-Key' => $this->api_key,
			'Accept'    => 'application/json',
		])->post($this->aether_url . '/realms/' . $this->uri_realm, [
			'action' => $action,
			'data'   => $data,
		]);


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
}
