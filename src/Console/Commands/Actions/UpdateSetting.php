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

        $urlSetting = "{$baseUrl}/applications/{$uriApplication}/actions/{$selectedUri}/realm-action-setting";
        $settingResponse = Http::withToken($token)->acceptJson()->get($urlSetting, [
            'uri_realm' => $realmId,
        ]);

        $currentSettings = $settingResponse->json()['data'] ?? [];
        $currentTriggers = $currentSettings['triggers'] ?? [];
        $triggersAreEmpty = empty($currentTriggers);

        $errorTrigger = isset($currentTriggers['error'][0])
            ? [
                "type" => "email",
                "send_at" => $currentTriggers['error'][0]['send_at'] ?? null,
                "cooldown" => $currentTriggers['error'][0]['cooldown'] ?? "00:15:00",
                "attendants" => $currentTriggers['error'][0]['attendants'] ?? []
            ]
            : null;

        $warningTrigger = isset($currentTriggers['warning'][0])
            ? [
                "type" => "email",
                "send_at" => $currentTriggers['warning'][0]['send_at'] ?? null,
                "cooldown" => $currentTriggers['warning'][0]['cooldown'] ?? "00:15:00",
                "attendants" => $currentTriggers['warning'][0]['attendants'] ?? []
            ]
            : null;

        $realmSettings = [];

        do {
            $this->line("\n¿Qué deseas modificar?");
            $options = [
                '0' => 'Cantidad máxima de errores',
                '1' => 'Cantidad máxima de warnings',
                '2' => 'Correos para warnings',
                '3' => 'Correos para errores',
                '4' => 'Cambiar cooldown warning',
                '5' => 'Cambiar cooldown error',
                '6' => 'Ver configuración actual',
                '7' => 'Guardar y salir',
                '8' => 'Cancelar',
            ];

            $selectedValue = $this->choice("Selecciona una opción:", array_values($options));
            $option = array_search($selectedValue, $options);

            switch ($option) {
                case '0':
                    $value = $this->ask("Ingresa la cantidad de errores que puede tener tu aplicación (solo número)");
                    if (is_numeric($value)) {
                        $realmSettings['fail_threshold'] = (int)$value;
                    }
                    break;

                case '1':
                    $value = $this->ask("Ingresa la cantidad de warnings que puede tener tu aplicación (solo número)");
                    if (is_numeric($value)) {
                        $realmSettings['warning_threshold'] = (int)$value;
                    }
                    break;
                case '2':
                    $emails = $this->ask("Correos para warnings (coma separados)");
                    $array = array_map('trim', explode(',', $emails));

                    if (!$warningTrigger) {
                        $warningTrigger = [
                            "type" => "email",
                            "send_at" => null,
                            "cooldown" => "00:15:00",
                            "attendants" => $array
                        ];
                    } else {
                        $warningTrigger["attendants"] = $array;
                    }

                    $realmSettings['triggers']['warning'] = [$warningTrigger];
                    break;
                case '3':
                    $emails = $this->ask("Correos para errores (coma separados)");
                    $array = array_map('trim', explode(',', $emails));

                    if (!$errorTrigger) {
                        $errorTrigger = [
                            "type" => "email",
                            "send_at" => null,
                            "cooldown" => "00:15:00",
                            "attendants" => $array
                        ];
                    } else {
                        $errorTrigger["attendants"] = $array;
                    }

                    $realmSettings['triggers']['error'] = [$errorTrigger];
                    break;
                case '4':
                    $cooldown = $this->ask("Nuevo cooldown warning (HH:MM:SS)");
                    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $cooldown)) {
                        $this->error("Formato inválido.");
                        break;
                    }

                    if (!$warningTrigger) {
                        $warningTrigger = [
                            "type" => "email",
                            "send_at" => null,
                            "cooldown" => $cooldown,
                            "attendants" => []
                        ];
                    } else {
                        $warningTrigger['cooldown'] = $cooldown;
                    }

                    $realmSettings['triggers']['warning'] = [$warningTrigger];
                    break;
                case '5':
                    $cooldown = $this->ask("Nuevo cooldown error (HH:MM:SS)");
                    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $cooldown)) {
                        $this->error("Formato inválido.");
                        break;
                    }
                    if (!$errorTrigger) {
                        $errorTrigger = [
                            "type" => "email",
                            "send_at" => null,
                            "cooldown" => $cooldown,
                            "attendants" => []
                        ];
                    } else {
                        $errorTrigger['cooldown'] = $cooldown;
                    }

                    $realmSettings['triggers']['error'] = [$errorTrigger];
                    break;
                case '6':
                    $this->info(json_encode($currentSettings, JSON_PRETTY_PRINT));
                    break;
                case '7':
                    break 2;
                case '8':
                    $this->warn("Operación cancelada.");
                    return 0;
            }
        } while (true);

        if ($triggersAreEmpty && !isset($realmSettings['triggers'])) {
        } else {
            $realmSettings['triggers'] = [
                "error" => $errorTrigger ? [$errorTrigger] : [],
                "warning" => $warningTrigger ? [$warningTrigger] : []
            ];
        }

        if (empty($realmSettings)) {
            $this->warn("No se realizaron cambios.");
            return 0;
        }

        $payload = array_merge(
            ["uri_realm" => $realmId],
            $realmSettings
        );

        $updateUrl = "{$baseUrl}/applications/{$uriApplication}/actions/{$selectedUri}/update-realm-action-setting";

        $updateResponse = Http::withToken($token)->acceptJson()->put($updateUrl, $payload);

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
