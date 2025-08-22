<?php

namespace Ometra\AetherClient;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AetherClient
{
	protected string $aether_url;
	protected string $uri_realm;

	public function __construct()
	{
		$this->aether_url = config('aether-client.base_url');
		$this->uri_realm  = config('aether-client.realm_id');
	}

	public function report(string $actionName): void
	{
		$response = Http::post($this->aether_url . '/api/realms/' . $this->uri_realm, [
			'action' => $actionName
		]);

		if ($response->ok()) {
			Log::channel('aether')->info('Action reported -> ' . $actionName);
		} else {
			Log::channel('aether')->alert('Failed to report action -> ' . $actionName);
		}
	}
}
