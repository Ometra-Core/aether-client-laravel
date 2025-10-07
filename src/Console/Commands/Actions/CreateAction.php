<?php

namespace Ometra\AetherClient\Console\Commands\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Ometra\AetherClient\AetherClient;
use Exception;

class CreateAction extends Command
{
    protected $signature = 'aether:create-action';
    protected $description = 'Create a new action in the Aether system';

    public function handle()
    {
        try {
            $client = new AetherClient();
            $baseUrl = $client->getBaseUrl();
            $token = $client->getToken();
            $uriApplication = $client->getUriApplication();
            $logLevel = $client->getLogLevel();
            $realId = $client->getRealmId();

            if (!$uriApplication) {
                $this->error("No se pudo obtener la URI de la aplicación desde el token.");
                return 1;
            }

            $this->info("-------Crear nueva acción-------");
            $name = $this->ask("Nombre de la acción (único)");
            $description = $this->ask("Descripción de la acción");
            $frequency = $this->ask("Frecuencia de reporte (en minutos)");

            if (!$this->confirm("¿Crear la acción '{$name}' con descripción '{$description}' en el realm '{$realId}'?")) {
                $this->info("Operación cancelada.");
                return 0;
            }

            $payload = [
                'name' => $name,
                'description' => $description,
                'frequency' => $frequency,
                'realms' => [$realId],
            ];

            $url = "{$baseUrl}/applications/{$uriApplication}/actions";

            $response = Http::withToken($token)->withHeaders([
                'Accept' => 'application/json',
            ])->post($url, $payload);

            if (!$response->ok()) {
                $this->error("Error al crear la acción.");
                Log::channel('aether')->error("Request fallida a $url: " . $response->body());
                return 1;
            }

            $responseData = $response->json();

            $status = $responseData['status'] ?? null;
            $message = $responseData['message'] ?? 'Sin mensaje';
            $uri_action = $responseData['data']['uri_action'] ?? null;

            if ($status !== 201) {
                $this->error("Error del servidor: $message");
                Log::channel('aether')->error("Respuesta con error desde $url: $message");
                return 1;
            }

            if ($logLevel === 'debug') {
                Log::channel('aether')->debug("Acción creada correctamente: {$name} ({$uri_action}).");
            }
            $this->info("Acción creada correctamente: {$name} ({$uri_action}).");
        } catch (Exception $e) {
            Log::channel('aether')->error("Excepción en aether:create-action -> " . $e->getMessage());
            $this->error("Error inesperado: " . $e->getMessage());
            return 1;
        }
    }
}
