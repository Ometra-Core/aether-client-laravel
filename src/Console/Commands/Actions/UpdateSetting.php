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
                "{$a['name']} - {$a['description']}" => $a['uri_action']
            ])
            ->toArray();

        $uriAction = select(
            label: 'Selecciona una acción:',
            options: $choices
        );

        $currentSettings = $this->actionApi->getSetting($uriAction) ?? [];
        $triggers = $currentSettings['triggers'] ?? [];

        while (true) {
            $type = select(
                label: '¿Qué deseas configurar?',
                options: [
                    'warning' => 'Warning',
                    'error'   => 'Error',
                    'exit'    => 'Salir'
                ]
            );

            if ($type === 'exit') {
                return 0;
            }

            $this->triggerMenu(
                $type,
                $triggers[$type][0] ?? null,
                $uriAction
            );
        }
    }

    protected function triggerMenu(string $type, ?array $trigger, string $uriAction): void
    {
        $trigger ??= [
            'type' => 'email',
            'send_at' => null,
            'cooldown' => '00:15:00',
            'attendants' => [],
        ];

        $extraSettings = [];

        while (true) {
            $option = select(
                label: strtoupper($type) . ' – ¿Qué deseas hacer?',
                options: [
                    'threshold' => 'Cambiar umbral',
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
                    $extraSettings["{$type}_threshold"] = (int) text(
                        label: "Nuevo umbral para {$type}:"
                    );
                    break;

                case 'cooldown':
                    $trigger['cooldown'] = text(
                        label: 'Tiempo entre notificaciones (HH:MM:SS)',
                        default: $trigger['cooldown']
                    );
                    break;

                case 'emails':
                    $trigger['attendants'] = array_map(
                        'trim',
                        explode(',', text('Correos (separados por coma):'))
                    );
                    break;

                case 'view':
                    $this->info(
                        json_encode($trigger, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    );
                    break;

                case 'delete':
                    $trigger = null;
                    break;

                case 'save':
                    $this->saveTrigger(
                        $uriAction,
                        $type,
                        $trigger,
                        $extraSettings
                    );
                    break;

                case 'back':
                    return;
            }
        }
    }

    protected function saveTrigger(string $uriAction, string $type, ?array $trigger, array $extra): void
    {
        $payload = array_merge(
            ['uri_realm' => $this->realm_id],
            $extra,
            [
                'triggers' => [
                    $type => $trigger ? [$trigger] : []
                ]
            ]
        );

        $this->actionApi->updateSetting($uriAction, $payload);

        $this->info('Configuración guardada correctamente.');

        if ($this->log_level === 'debug') {
            Log::channel('aether')->debug(
                "Realm settings actualizados ({$type}) para acción {$uriAction}"
            );
        }
    }
}
