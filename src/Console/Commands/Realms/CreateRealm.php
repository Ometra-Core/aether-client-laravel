<?php

namespace Ometra\AetherClient\Console\Commands\Actions;

use Ometra\AetherClient\Console\BaseCommands;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CreateAction extends BaseCommands
{
    protected $signature = 'aether:create-realm';
    protected $description = 'Create a new aether in the Aether';

    public function handle()
    {
        try {
            $baseUrl = $this->base_url;
            $token = $this->token;
            $uriApplication = $this->getUriApplication();
            $logLevel = $this->log_level;
            $realId = $this->realm_id;

            if (!$uriApplication) {
                $this->error("No se pudo obtener la URI de la aplicación desde el token.");
                return 1;
            }

            $this->info("-------Crear un nuevo realm-------");
            $name = $this->ask("Nombre de la realm (único)");
            $numActions = (int) $this->ask("¿Cuántas acciones quieres agregar?");
            $actions = [];
            for ($i = 1; $i <= $numActions; $i++) {
                $this->info("Configurando acción #{$i}");
                $actionName = $this->ask("Nombre de la acción");
                $description = $this->ask("Descripción de la acción");
                $frequency = (int) $this->ask("Frecuencia de reporte (en minutos)");

                if (!$this->confirm("¿Crear la acción '{$actionName}' con descripción '{$description}' y frecuencia '{$frequency}' minutos?")) {
                    $this->info("Acción #{$i} cancelada.");
                    continue;
                }

                $actions[] = [
                    'name' => $actionName,
                    'description' => $description,
                    'frequency' => $frequency,
                ];
            }

            if (empty($actions)) {
                $this->error("No se agregó ninguna acción. Operación cancelada.");
                return 1;
            }

            if (!$this->confirm("¿Deseas crear este realm con estas acciones?")) {
                $this->info("Operación cancelada.");
                return 0;
            }

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

            if ($status !== 200) {
                $this->error("Error del servidor: $message");
                Log::channel('aether')->error("Respuesta con error desde $url: $message");
                return 1;
            }

            if ($logLevel === 'debug') {
                Log::channel('aether')->debug("Acción creada correctamente: {$name} ({$uri_action}).");
                return 0;
            }
            $this->info("Acción creada correctamente: {$name}.");
        } catch (Exception $e) {
            Log::channel('aether')->error("Excepción en aether:create-action -> " . $e->getMessage());
            $this->error("Error inesperado: " . $e->getMessage());
            return 1;
        }
    }
}
