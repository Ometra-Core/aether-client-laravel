<?php

namespace Ometra\AetherClient\Console\Commands\Realms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Ometra\AetherClient\Console\BaseCommands;

class UpdateRealm extends BaseCommands
{
    protected $signature = 'aether:update-realm';
    protected $description = 'Update realm to aether';

    public function handle()
    {
        $baseUrl = $this->base_url;
        $token = $this->token;
        $uriApplication = $this->getUriApplication();
        $uriRealm = $this->realm_id;
        $logLevel = $this->log_level;

        $realmUrl = "{$baseUrl}/applications/{$uriApplication}/realms/{$uriRealm}";
        $response = Http::withToken($token)
            ->withHeaders(['Accept' => 'application/json'])
            ->get($realmUrl);

        if (!$response->ok()) {
            $this->error("No se pudo obtener el Realm.");
            return 1;
        }

        $data = $response->json()['data'] ?? null;

        if (!$data) {
            $this->error("Datos del Realm no disponibles.");
            return 1;
        }

        $currentName = $data['name'] ?? 'Sin nombre';
        $actions = $data['action'] ?? [];

        $newName = $currentName;
        $newActions = [];

        do {
            $options = [
                '0' => 'Cambiar nombre',
                '1' => 'Agregar nuevas acciones',
                '2' => 'Ver valores actuales',
                '3' => 'Guardar y salir',
                '4' => 'Cancelar',
            ];

            $selectedValue = $this->choice("Selecciona una opción", array_values($options));
            $option = array_search($selectedValue, $options);

            switch ($option) {
                case '0':
                    $newName = $this->ask("Nuevo nombre del Realm", $newName);
                    break;

                case '1':
                    do {
                        $newAction = [
                            'name' => $this->ask("Nombre de la nueva acción"),
                            'description' => $this->ask("Descripción"),
                            'frequency' => (int) $this->ask("Frecuencia (en minutos)"),
                        ];

                        $newActions[] = $newAction;
                    } while ($this->confirm("¿Deseas agregar otra acción?", false));
                    break;

                case '2':
                    $this->showCurrentValues($currentName, $actions);
                    break;

                case '3':
                    $this->line("\nResumen de cambios:");
                    $this->line("Nombre del Realm: $newName");
                    if (!empty($newActions)) {
                        $this->line("Nuevas acciones:");
                        foreach ($newActions as $action) {
                            $this->line("- {$action['name']} | {$action['description']} | {$action['frequency']} min");
                        }
                    } else {
                        $this->line("No se agregaron nuevas acciones.");
                    }

                    if (!$this->confirm("¿Deseas guardar estos cambios?", true)) {
                        $this->info("⚠️ Cambios descartados.");
                        return 0;
                    }

                    $payload = ['name' => $newName];
                    if (!empty($newActions)) {
                        $payload['actions'] = $newActions;
                    }

                    $updateUrl = "{$baseUrl}/applications/{$uriApplication}/realms/{$uriRealm}/update";
                    $updateResponse = Http::withToken($token)
                        ->withHeaders(['Accept' => 'application/json'])
                        ->put($updateUrl, $payload);

                    if (!$updateResponse->ok()) {
                        $this->error("Error al actualizar el Realm.");
                        Log::channel('aether')->error("Fallo al actualizar Realm: " . $updateResponse->body());
                        return 1;
                    }

                    $this->info("Realm actualizado correctamente.");
                    if ($logLevel === 'debug') {
                        Log::channel('aether')->debug("Realm actualizado: {$uriRealm}");
                    }
                    return 0;

                case '4':
                    $this->info("Operación cancelada. No se realizaron cambios.");
                    return 0;

                default:
                    $this->warn("Opción no válida.");
                    break;
            }
        } while (true);
    }

    protected function showCurrentValues(string $name, array $actions): void
    {
        $this->line("\nValores actuales del Realm:");
        $this->line("Nombre: {$name}");

        if (!empty($actions)) {
            $this->line("Acciones:");
            foreach ($actions as $action) {
                $this->line(" - {$action['name']} | {$action['description']} | Freq: {$action['frequency']} min");
            }
        } else {
            $this->line("No hay acciones asociadas actualmente.");
        }
    }
}
