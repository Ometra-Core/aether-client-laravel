<?php

use Illuminate\Console\Command;
use Ometra\AetherClient\Console\Commands\Frequency;
use Ometra\AetherClient\Entities\Action;
use function Laravel\Prompts\{form, text, select, info, error};

class CreateAction extends Command
{
    protected $signature = 'aether:create-action';
    protected $description = 'Create a new action in Aether';

    public function __construct(protected Action $actionsApi)
    {
        parent::__construct();
    }

    public function handle()
    {
        info('<fg=cyan>Crear una nueva acción</>');

        $data = form()
            ->text('Nombre', name: 'name', required: 'El nombre es obligatorio')
            ->text('Descripción', name: 'description', required: 'La descripción es obligatoria')
            ->select('Tipo de frecuencia', name: 'type', options: Frequency::TYPES)
            ->submit();

        if ($data['type'] === 'minutes') {
            $frequency = text(
                label: '¿Cada cuántos minutos?',
                validate: fn($v) =>
                filter_var($v, FILTER_VALIDATE_INT) && (int)$v > 0 ? null : 'Debe ser un entero mayor a 0'
            );
        }

        if ($data['type'] === 'cron') {
            $key = select(
                label: 'Frecuencia CRON',
                options: Frequency::CRON_OPTIONS
            );

            $frequency = Frequency::cron($key);
        }

        $payload = [
            'name'        => $data['name'],
            'description' => $data['description'],
            'frequency'   => $frequency,
        ];

        $update = $this->actionsApi->create($payload);

        if ($update === true) {
            info('Acción creada correctamente.');
            return self::SUCCESS;
        } else {
            error('Error al crear la acción.');
            return self::FAILURE;
        }
    }
}
