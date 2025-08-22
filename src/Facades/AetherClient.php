<?php

namespace Ometra\AetherClient\Facades;

use Illuminate\Support\Facades\Facade;

class AetherClient extends Facade
{
	protected static function getFacadeAccessor()
	{
		return \Ometra\AetherClient\AetherClient::class;
	}
}
