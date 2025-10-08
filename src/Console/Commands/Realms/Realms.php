<?php

namespace Ometra\AetherClient\Console\Commands\realms;

use Ometra\AetherClient\Console\BaseCommands;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Ometra\AetherClient\AetherClient;
use Exception;

class realms extends BaseCommands
{
    protected $signature = 'aether:realms';
    protected $description = 'List the realms that the application has';

    public function handle()
    {
        try {
            $baseUrl = $this->base_url;
            $token = $this->token;
            $uriApplication = $this->getUriApplication();
            $logLevel = $this->log_level;

            if (!$uriApplication) {
                $this->error("No se pudo obtener la URI de la aplicación desde el token.");
            }

            $url = "{$baseUrl}/applications/{$uriApplication}/realms";

            $response = Http::withToken($token)->withHeaders([
                'Accept' => 'application/json',
            ])->get($url);

            if (!$response->ok()) {
                $this->error("Error al obtener los realms.");
                Log::channel('aether')->error("Request fallida a $url: " . $response->body());
                return 1;
            }

            $responseData = $response->json();

            $status = $responseData['status'] ?? null;
            $message = $responseData['message'] ?? 'Sin mensaje';

            if ($status !== 200) {
                $this->error("Error del servidor: $message");
                Log::channel('aether')->error("Respuesta con error desde $url: $message");
                return 1;
            }

            $realms = $responseData['data'] ?? [];

            if ($logLevel === 'debug') {
                Log::channel('aether')->debug("Realms recuperados correctamente desde {$url}.");
                return 0;
            }

            if (empty($realms)) {
                $this->info("No hay realms disponibles.");
                return 0;
            }

            $this->info("Realms disponibles:\n");

            foreach ($realms as $realm) {
                $this->line("URI realm: {$realm['uri_realm']}");
                $this->line("Nombre: {$realm['name']}");
                $this->line(str_repeat('-', 40));
            }
            return 0;
        } catch (Exception $e) {
            Log::channel('aether')->error("Excepción en aether:realms -> " . $e->getMessage());
            $this->error("Error inesperado: " . $e->getMessage());
            return 1;
        }
    }
}
