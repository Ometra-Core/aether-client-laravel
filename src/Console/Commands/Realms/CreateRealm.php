<?php

namespace Ometra\AetherClient\Console\Commands\Realms;

use function Laravel\Prompts\{form, info, warning, confirm, select};
use Ometra\AetherClient\Entities\Realm;
use Illuminate\Console\Command;

class CreateRealm extends Command
{
    protected $signature = 'aether:create-realm';
    protected $description = 'Create a new realm in the Aether';

    public function __construct(protected Realm $realmApi)
    {
        parent::__construct();
    }

    public function handle()
    {
        $data = form()
            ->text('Nombre', name: 'name', required: 'El nombre es obligatorio')
            ->text('Descripción', name: 'description', required: 'La descripción es obligatoria')
            ->submit();

        $payload = [
            'name'        => $data['name'],
            'description' => $data['description'],
        ];

        $create = $this->realmApi->create($payload);

        if ($create === true) {
            info('Realm creado correctamente.');
            return self::SUCCESS;
        } else {
            warning('Error al crear el realm.');
            return self::FAILURE;
        }
    }
}
