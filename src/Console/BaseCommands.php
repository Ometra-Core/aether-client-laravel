<?php

namespace Ometra\AetherClient\Console;

use Illuminate\Console\Command;

abstract class BaseCommands extends Command
{
    protected string $base_url;
    protected string $realm_id;
    protected string $api_key;
    protected string $log_level;
    protected string $token;

    public function __construct()
    {
        parent::__construct();
        $this->base_url = config('aether-client.base_url');
        $this->realm_id = config('aether-client.realm_id');
        $this->api_key = config('aether-client.api_key');
        $this->log_level = strtolower(config('aether-client.log_level', 'error'));
        $this->token = config('aether-client.token', null);
    }

    protected function getUriApplication(): ?string
    {
        $decode = base64_decode($this->token);
        $parts = explode('?', $decode);
        return $parts[0] ?? null;
    }
}
