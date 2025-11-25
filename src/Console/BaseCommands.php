<?php

namespace Ometra\AetherClient\Console;

use Illuminate\Console\Command;

abstract class BaseCommands extends Command
{
    protected string $base_url;
    protected string $realm_id;
    protected string $log_level;
    protected string $token;

    protected array $cronOptions = [
        'every_minute' => 'Cada minuto',
        'everyFiveMinutes' => 'Cada 5 minutos',
        'everyTenMinutes' => 'Cada 10 minutos',
        'everyThirtyMinutes' => 'Cada 30 minutos',
        'hourly' => 'Cada hora',
        'daily' => 'Todos los días a la medianoche',
        'weekly' => 'Todos los domingos a la medianoche',
        'monthly' => 'El primer día de cada mes a la medianoche',
    ];

    protected array $cronMap = [
        'every_minute' => '* * * * *',
        'everyFiveMinutes' => '*/5 * * * *',
        'everyTenMinutes' => '*/10 * * * *',
        'everyThirtyMinutes' => '*/30 * * * *',
        'hourly' => '0 * * * *',
        'daily' => '0 0 * * *',
        'weekly' => '0 0 * * 0',
        'monthly' => '0 0 1 * *',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->base_url = config('aether-client.base_url');
        $this->realm_id = config('aether-client.realm_id');
        $this->log_level = strtolower(config('aether-client.log_level', 'error'));
        $this->token = config('aether-client.token', null);
    }

    protected function getUriApplication(): ?string
    {
        $decode = base64_decode($this->token);
        $parts = explode(':', $decode);
        return $parts[0] ?? null;
    }

    protected function getCronOptions(): array
    {
        return $this->cronOptions;
    }

    protected function getCronMap(): array
    {
        return $this->cronMap;
    }
}
