<?php

namespace Ometra\AetherClient;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Ometra\AetherClient\Console\Commands\AetherReport;
use Ometra\AetherClient\Console\Commands\Actions\Actions;
use Ometra\AetherClient\Console\Commands\Actions\CreateAction;
use Ometra\AetherClient\Console\Commands\Actions\UpdateAction;
use Ometra\AetherClient\Console\Commands\Actions\UpdateSetting;
use Ometra\AetherClient\Console\Commands\Actions\DeleteAction;
use Ometra\AetherClient\Console\Commands\Realms\CreateRealm;
use Ometra\AetherClient\Console\Commands\Realms\Realms;
use Ometra\AetherClient\Console\Commands\Realms\UpdateRealm;

class AetherClientServiceProvider extends ServiceProvider
{
	public function register()
	{
		$this->mergeConfigFrom(
			__DIR__ . '/../config/aether-client.php',
			'aether-client'
		);

		$this->app->singleton(AetherClient::class, function ($app) {
			return new AetherClient();
		});
	}

	public function boot()
	{
		$this->publishes([
			__DIR__ . '/../config/aether-client.php' => config_path('aether-client.php'),
		], 'config');

		$this->app['config']->set('logging.channels.aether', [
			'driver' => 'aether',
		]);

		Log::extend('aether', function ($app, array $config) {
			$logger = new Logger('aether');
			$handler = new StreamHandler(storage_path('logs/aether.log'), Logger::DEBUG);
			$handler->setFormatter(new LineFormatter(null, null, true, true));
			$logger->pushHandler($handler);

			return $logger;
		});

		$this->loadRoutesFrom(__DIR__ . '/../routes/console.php');

		if ($this->app->runningInConsole()) {
			$this->commands([
				AetherReport::class,
				Actions::class,
				CreateAction::class,
				UpdateAction::class,
				DeleteAction::class,
				Realms::class,
				CreateRealm::class,
				UpdateRealm::class,
				UpdateSetting::class,
			]);
		}
	}
}
