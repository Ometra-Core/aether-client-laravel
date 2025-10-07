<?php

namespace Ometra\AetherClient\Console\Commands\Actions;

use Ometra\AetherClient\Console\BaseCommands;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class DeleteAction extends BaseCommands
{
    protected $signature = 'aether:delete-action {uri_action}';
    protected $description = 'Delete a specific action by its URI';

    public function handle()
    {
        try {
            $baseUrl = $this->base_url;
            $token = $this->token;
            $uriApplication = $this->getUriApplication();
            $logLevel = $this->log_level;
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

            if ($logLevel === 'debug') {
                Log::channel('aether')->debug("Acción eliminada correctamente: {$uri_action}.");
                return 0;
            }
            $this->info("Acción eliminada correctamente: {$uri_action}.");
            
        } catch (Exception $e) {
            Log::channel('aether')->error("Excepción en aether:actions -> " . $e->getMessage());
            $this->error("Error inesperado: " . $e->getMessage());
            return 1;
        }
    }
}
