<?php

namespace Ometra\AetherClient\Console\Commands\Actions;

use Ometra\AetherClient\Console\BaseCommands;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CreateAction extends BaseCommands
{
    protected $signature = 'aether:create-action';
    protected $description = 'Create a new action in the Aether system';

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

            $this->info("-------Crear nueva acción-------");
            $name = $this->ask("Nombre de la acción (único)");
            $description = $this->ask("Descripción de la acción");

            $options = [
                '1' => 'Ingresar manualmente en minutos',
                '2' => 'Seleccionar un cron de Laravel',
            ];
            $typeFrecuency = $this->choice(
                '¿Cómo deseas establecer la frecuencia?',
                array_values($options)
            );

            $option = array_search($typeFrecuency, $options);
            $frequency = null;

            switch ($option) {
                case '1':
                    $frequency = $this->ask("Frecuencia de reporte (en minutos)");
                    break;
                case '2':
                    $cronOptions = $this->getCronOptions();
                    $cronMap = $this->getCronMap();
                    $selectedCron = $this->choice('¿Qué cron deseas usar?', array_keys($cronOptions), 0);
                    $cronDescription = $cronOptions[$selectedCron];
                    $this->info("Has seleccionado: $cronDescription");
                    $frequency = $cronMap[$selectedCron];
                    break;
            }

            if (!$this->confirm("¿Deseas crear la acción '{$name}' con descripción: '{$description}' y frecuencia: '" . ($cronDescription ?? $frequency) . "' ?")) {
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
