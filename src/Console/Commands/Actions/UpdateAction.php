<?php

namespace Ometra\AetherClient\Console\Commands\Actions;

use Ometra\AetherClient\Console\BaseCommands;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateAction extends BaseCommands
{
    protected $signature = 'aether:update-action';
    protected $description = 'Update a specific action by its URI';

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

        $choices = [];
        foreach ($actions as $action) {
            $label = "{$action['name']} - {$action['description']}";
            $choices[$label] = $action['uri_action'];
        }

        $selectedLabel = $this->choice("Selecciona una acción para editar:", array_keys($choices));
        $selectedUri = $choices[$selectedLabel];
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

            $options = [
                '0' => 'Nombre',
                '1' => 'Descripción',
                '2' => 'Frecuencia',
                '3' => 'Ver valores actuales',
                '4' => 'Guardar y salir',
                '5' => 'Cancelar',
            ];

            $selectedValue = $this->choice("¿Qué deseas modificar?", array_values($options));
            $option = array_search($selectedValue, $options);

            switch ($option) {
                case '0':
                    $newName = $this->ask("Nuevo nombre", $newName);
                    break;
                case '1':
                    $newDescription = $this->ask("Nueva descripción", $newDescription);
                    break;
                case '2':
                    $newFrequency = $this->ask("Nueva frecuencia (en minutos)", $newFrequency);
                    break;
                case '3':
                    $this->showCurrentValues($newName, $newDescription, $newFrequency);
                    break;
                case '4':
                    break 2;
                case '5':
                    $this->warn("Operación cancelada.");
                    return;
            }
        } while (true);


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
        ])->put($updateUrl, $payload);

        if (!$updateResponse->ok()) {
            $this->error("Error al actualizar la acción.");
            Log::channel('aether')->error("Update fallido: " . $updateResponse->body());
            return 1;
        }

        $this->info("Acción actualizada correctamente.");
        if ($logLevel === 'debug') {
            Log::channel('aether')->debug("Acción: ({$selectedUri}) actualizada correctamente.");
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
