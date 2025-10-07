<?php

namespace Ometra\AetherClient\Console\Commands\Actions;

use Ometra\AetherClient\Console\BaseCommands;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateAction extends BaseCommands
{
    protected $signature = 'aether:update-action';
    protected $description = 'Actualizar una acción existente mediante un menú interactivo';

    public function handle()
    {
        $baseUrl = $this->base_url;
        $token = $this->token;
        $uriApplication = $this->getUriApplication();
        $realmId = $this->realm_id;
        $logLevel = $this->log_level;

        $url = "{$baseUrl}/applications/{$uriApplication}/actions";
        $response = Http::withToken($token)->withHeaders([
            'Accept' => 'application/json',
        ])->get($url);

        if (!$response->ok()) {
            $this->error("No se pudieron obtener las acciones.");
            return 1;
        }

        $actions = $response->json()['data'] ?? [];

        if (empty($actions)) {
            $this->warn("No hay acciones registradas.");
            return 0;
        }

        $choices = collect($actions)->pluck('name', 'uri_action')->toArray();
        $selectedUri = $this->choice("Selecciona una acción para editar:", $choices);
        $selectedAction = collect($actions)->firstWhere('uri_action', $selectedUri);

        if (!$selectedAction) {
            $this->error("Acción no encontrada.");
            return 1;
        }

        $this->info("Has seleccionado: {$selectedAction['name']}");

        $newName = $selectedAction['name'];
        $newDescription = $selectedAction['description'];
        $newFrequency = $selectedAction['frequency'];

        do {
            $this->line("\n¿Qué deseas modificar?");
            $option = $this->choice("Opciones", [
                '1' => 'Nombre',
                '2' => 'Descripción',
                '3' => 'Frecuencia',
                '4' => 'Ver valores actuales',
                '5' => 'Guardar y salir',
                '6' => 'Cancelar',
            ], '1');

            match ($option) {
                '1' => $newName = $this->ask("Nuevo nombre", $newName),
                '2' => $newDescription = $this->ask("Nueva descripción", $newDescription),
                '3' => $newFrequency = $this->ask("Nueva frecuencia (en minutos)", $newFrequency),
                '4' => $this->showCurrentValues($newName, $newDescription, $newFrequency),
                '6' => function () {
                    $this->warn("Operación cancelada.");
                    exit(0);
                }
            };
        } while ($option !== '5');


        $this->line("\nResumen de los nuevos valores:");
        $this->line("Nombre: $newName");
        $this->line("Descripción: $newDescription");
        $this->line("Frecuencia: $newFrequency minutos");

        if (!$this->confirm("¿Deseas guardar estos cambios?", true)) {
            $this->info("No se realizaron cambios.");
            return 0;
        }

        $updateUrl = "{$baseUrl}/applications/{$uriApplication}/actions/{$selectedUri}/update";

        $payload = [
            'name' => $newName,
            'description' => $newDescription,
            'frequency' => $newFrequency,
            'realms' => [$realmId],
        ];

        $updateResponse = Http::withToken($token)->withHeaders([
            'Accept' => 'application/json',
        ])->post($updateUrl, $payload);

        if (!$updateResponse->ok()) {
            $this->error("Error al actualizar la acción.");
            Log::channel('aether')->error("Update fallido: " . $updateResponse->body());
            return 1;
        }

        if ($logLevel === 'debug') {
            Log::channel('aether')->debug("Acción: ({$selectedUri}) actualizada correctamente.");
            $this->info("Acción actualizada correctamente.");
            return 0;
        }
    }

    protected function showCurrentValues(string $name, string $desc, string $freq): void
    {
        $this->line("\nValores actuales:");
        $this->line("Nombre      : $name");
        $this->line("Descripción : $desc");
        $this->line("Frecuencia  : $freq minutos");
    }
}
