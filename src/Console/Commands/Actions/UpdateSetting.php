<?php

namespace Ometra\AetherClient\Console\Commands\Actions;

use Ometra\AetherClient\Console\BaseCommands;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateSetting extends BaseCommands
{
    protected $signature = 'aether:update-action-realm';
    protected $description = 'Update realm settings for a specific action';

    public function handle()
    {
        $baseUrl = $this->base_url;
        $token = $this->token;
        $uriApplication = $this->getUriApplication();
        $realmId = $this->realm_id;
        $logLevel = $this->log_level;

        $url = "{$baseUrl}/applications/{$uriApplication}/actions";
        $response = Http::withToken($token)->acceptJson()->get($url);

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

        $this->info("Has seleccionado la acción con URI: {$selectedUri}");

        $realmSettings = [];

        do {
            $this->line("\n¿Qué deseas modificar?");
            $options = [
                '0' => 'fail_threshold (errores)',
                '1' => 'warning_threshold (warnings)',
                '2' => 'Lista de correos para warnings',
                '3' => 'Lista de correos para errores',
                '4' => 'Guardar y salir',
                '5' => 'Cancelar',
            ];

            $selectedValue = $this->choice("Selecciona una opción:", array_values($options));
            $option = array_search($selectedValue, $options);

            switch ($option) {
                case '0':
                    $value = $this->ask("Nuevo fail_threshold (solo número)");
                    if (is_numeric($value)) {
                        $realmSettings['fail_threshold'] = (int)$value;
                    }
                    break;

                case '1':
                    $value = $this->ask("Nuevo warning_threshold (solo número)");
                    if (is_numeric($value)) {
                        $realmSettings['warning_threshold'] = (int)$value;
                    }
                    break;

                case '2':
                    $emails = $this->ask("Correos para warnings (separados por coma)");
                    $array = array_map('trim', explode(',', $emails));

                    $realmSettings['triggers']['warning'] = [
                        [
                            "type" => "email",
                            "attendants" => $array
                        ]
                    ];
                    break;

                case '3':
                    $emails = $this->ask("Correos para errores (separados por coma)");
                    $array = array_map('trim', explode(',', $emails));

                    $realmSettings['triggers']['error'] = [
                        [
                            "type" => "email",
                            "attendants" => $array
                        ]
                    ];
                    break;

                case '4':
                    break 2;

                case '5':
                    $this->warn("Operación cancelada.");
                    return 0;
            }
        } while (true);

        if (empty($realmSettings)) {
            $this->warn("No se realizaron cambios.");
            return 0;
        }

        $payload = [
            "realms" => [
                array_merge(["uri_realm" => $realmId], $realmSettings)
            ]
        ];

        $updateUrl = "{$baseUrl}/applications/{$uriApplication}/actions/{$selectedUri}/update-realms-action-settings";

        $updateResponse = Http::withToken($token)
            ->acceptJson()
            ->put($updateUrl, $payload);

        if (!$updateResponse->ok()) {
            $this->error("Error al actualizar la acción.");
            Log::channel('aether')->error("Update fallido: " . $updateResponse->body());
            return 1;
        }

        $this->info("Realm settings actualizados correctamente.");

        if ($logLevel === 'debug') {
            Log::channel('aether')->debug("Realm settings actualizados para acción ({$selectedUri}).");
        }

        return 0;
    }
}
