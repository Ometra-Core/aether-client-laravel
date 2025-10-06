<?php

namespace Ometra\AetherClient\Console\Commands\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Ometra\AetherClient\AetherClient;
use Exception;

class ReadActions extends Command
{
    protected $signature = 'aether:actions';
    protected $description = 'List the actions that the application has';

    public function handle()
    {
        try {
            $client = new AetherClient();
            $baseUrl = $client->getBaseUrl();
            $token = $client->getToken();
            $uriApplication = $client->getUriApplication();
            $logLevel = $client->getLogLevel();

            if (!$uriApplication) {
                $this->error("No se pudo obtener la URI de la aplicación desde el token.");
            }

            $url = "{$baseUrl}/{$uriApplication}/actions";

            $response = Http::withToken($token)->withHeaders([
                'Accept' => 'application/json',
            ])->get($url);

            if (!$response->ok()) {
                $this->error("Error al obtener las acciones.");
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

            $actions = $responseData['data'] ?? [];

            if ($logLevel === 'debug') {
                Log::channel('aether')->debug("Acciones recuperadas correctamente desde {$url}.");
            }

            if (empty($actions)) {
                $this->info("No hay acciones disponibles.");
                return 0;
            }

            $this->info("Acciones disponibles:\n");

            foreach ($actions as $action) {
                $this->line("URI aplicación: {$action['uri_application']}");
                $this->line("Nombre: {$action['name']}");
                $this->line("Descripción: {$action['description']}");
                $this->line("Frecuencia: {$action['frequency']} minutos");
                $this->line(str_repeat('-', 40));
            }

            return 0;
        } catch (Exception $e) {
            Log::channel('aether')->error("Excepción en aether:actions -> " . $e->getMessage());
            $this->error("Error inesperado: " . $e->getMessage());
            return 1;
        }
    }
}
