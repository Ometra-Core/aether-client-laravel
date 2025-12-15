<?php

namespace Ometra\AetherClient\Console\Commands\Actions;

use Ometra\AetherClient\Console\BaseCommands;
use Ometra\AetherClient\Entities\Action;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\confirm;

class UpdateSetting extends BaseCommands
{
    protected $signature = 'aether:update-action-realm';
    protected $description = 'Update realm settings for a specific action';

    public function __construct(protected Action $actionApi)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $actions = $this->actionApi->index();

        if (empty($actions)) {
            $this->warn('No hay acciones registradas.');
            return 0;
        }

        $choices = collect($actions)
            ->mapWithKeys(fn($a) => [
                $a['uri_action'] => "{$a['name']} - {$a['description']}",
            ])
            ->toArray();

        $uriAction = select(
            label: 'Selecciona una acción:',
            options: $choices
        );

        $currentSettings = $this->actionApi->getSetting($uriAction) ?? [];
        $rawTriggers = $currentSettings['triggers'] ?? [];

        $triggers = [
            'warning' => $rawTriggers['warning'][0] ?? null,
            'error'   => $rawTriggers['error'][0] ?? null,
        ];

        $thresholds = [
            'warning' => $currentSettings['warning_threshold'] ?? null,
            'error'   => $currentSettings['fail_threshold'] ?? null,
        ];

        while (true) {
            $type = select(
                label: '¿Qué deseas configurar?',
                options: [
                    'warning' => 'Warning',
                    'error'   => 'Error',
                    'exit'    => 'Salir',
                ]
            );

            if ($type === 'exit') {
                return 0;
            }

            $this->triggerMenu(
                $type,
                $triggers,
                $thresholds,
                $uriAction
            );
        }
    }

    protected function triggerMenu(string $type, array &$triggers, array &$thresholds, string $uriAction): void
    {
        while (true) {
            $option = select(
                label: strtoupper($type) . ' ¿Qué deseas hacer?',
                options: [
                    'threshold' => 'Cambiar el umbral',
                    'cooldown'  => 'Cambiar tiempo entre notificaciones',
                    'emails'    => 'Correos para enviar notificaciones',
                    'view'      => 'Ver configuración actual',
                    'delete'    => 'Eliminar configuración',
                    'save'      => 'Guardar y salir',
                    'back'      => 'Regresar al menú principal',
                ]
            );

            switch ($option) {
                case 'threshold':
                    $thresholds[$type] = (int) text(
                        label: "Cantidad máxima de {$type}s:",
                        default: $thresholds[$type] ?? 0
                    );
                    break;

                case 'cooldown':
                    $triggers[$type] ??= $this->defaultTrigger();

                    $triggers[$type]['cooldown'] = text(
                        label: 'Tiempo entre notificaciones (HH:MM:SS)',
                        default: $triggers[$type]['cooldown']
                    );
                    break;

                case 'emails':
                    $triggers[$type] ??= $this->defaultTrigger();

                    $currentEmails = $triggers[$type]['attendants'] ?? [];

                    $input = text(
                        label: 'Correos nuevos (separados por coma). Deja vacío para no agregar:',
                        default: implode(', ', $currentEmails)
                    );

                    if (! empty(trim($input))) {
                        $newEmails = array_map('trim', explode(',', $input));
                        $triggers[$type]['attendants'] = array_values(
                            array_unique(array_merge($currentEmails, $newEmails))
                        );
                    }

                    break;

                case 'view':
                    $this->info(json_encode([
                        'threshold' => $thresholds[$type],
                        'trigger'   => $triggers[$type],
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    break;

                case 'delete':
                    if ($triggers[$type] === null) {
                        $this->warn('No hay configuración que eliminar.');
                        break;
                    }

                    if (confirm(
                        label: "¿Seguro que deseas eliminar la configuración {$type}?",
                        default: false
                    )) {
                        $triggers[$type] = null;
                        $this->warn("Configuración {$type} eliminada.");
                    }
                    break;

                case 'save':
                    $this->saveSetting(
                        $uriAction,
                        $triggers,
                        $thresholds
                    );
                    return;

                case 'back':
                    return;
            }
        }
    }

    protected function saveSetting(string $uriAction, array $triggers, array $thresholds): void
    {
        $payload = [
            'uri_realm' => $this->realm_id,
            'warning_threshold' => $thresholds['warning'],
            'fail_threshold'    => $thresholds['error'],
            'triggers' => [
                'warning' => $triggers['warning'] ? [$triggers['warning']] : [],
                'error'   => $triggers['error']   ? [$triggers['error']]   : [],
            ],
        ];

        if ($this->log_level === 'debug') {
            Log::channel('aether')->debug('Payload enviado', $payload);
        }

        $this->actionApi->updateSetting($uriAction, $payload);

        $this->info('Configuración guardada correctamente.');
    }

    protected function defaultTrigger(): array
    {
        return [
            'type'       => 'email',
            'send_at'    => null,
            'cooldown'   => '00:15:00',
            'attendants' => [],
        ];
    }
}
