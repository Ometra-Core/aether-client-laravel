<?php

return [
	'base_url' => env('AETHER_BASE_URL'),
	'realm_id' => env('AETHER_REALM_ID'),
	'api_key' => env('AETHER_API_KEY'),
	'log_level' => env('AETHER_LOG_LEVEL', 'error'),
	'token' => env('AETHER_TOKEN', null)
];
