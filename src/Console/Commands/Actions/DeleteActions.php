<?php

namespace Ometra\AetherClient\Console\Commands\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Ometra\AetherClient\AetherClient;
use Exception;

class DeleteActions extends Command
{
    protected $signature = 'aether:delete-actions {uri_action}';
    protected $description = 'Delete a specific action by its URI';

    public function handle()
    {
        try {
            $client = new AetherClient();
            $baseUrl = $client->getBaseUrl();
            $token = $client->getToken();
            $uriApplication = $client->getUriApplication();
            $logLevel = $client->getLogLevel();
            $uri_action = $this->argument('uri_action');

            if (!$uriApplication) {
                $this->error("No se pudo obtener la URI de la aplicación desde el token.");
            }

            $url = "{$baseUrl}/applications/{$uriApplication}/actions/$uri_action/destroy";

            $response = Http::withToken($token)->withHeaders([
                'Accept' => 'application/json',
            ])->get($url);

            if (!$response->ok()) {
                $this->error("Error al obtener eliminar la acción.");
                Log::channel('aether')->error("Request fallida a $url: " . $response->body());
            }

            $responseData = $response->json();

            $status = $responseData['status'] ?? null;
            $message = $responseData['message'] ?? 'Sin mensaje';

            if ($status !== 200) {
                $this->error("Error del servidor: $message");
                Log::channel('aether')->error("Respuesta con error desde $url: $message");
            }

            $actions = $responseData['data'] ?? [];

            if ($logLevel === 'debug') {
                Log::channel('aether')->debug("Acción eliminada correctamente: {$uri_action}.");
            }

            $this->info("Acción eliminada correctamente: {$uri_action}.");
        } catch (Exception $e) {
            Log::channel('aether')->error("Excepción en aether:actions -> " . $e->getMessage());
            $this->error("Error inesperado: " . $e->getMessage());
            return 1;
        }
    }
}
