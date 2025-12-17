<?php

namespace Ometra\AetherClient\Console\Commands\Actions;

use Illuminate\Console\Command;
use Ometra\AetherClient\Entities\Action;
use Ometra\AetherClient\Entities\Realm;
use function Laravel\Prompts\{select, multiselect, confirm, info, table, warning};

class AssociateRealm extends Command
{
    protected $signature = 'aether:associate-action-realm';
    protected $description = 'Associate one or more realms to a specific action';

    public function __construct(protected Action $actionApi, protected Realm $realmApi)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $actions = $this->actionApi->index(true);

        if (empty($actions)) {
            warning('No hay acciones disponibles.');
            return self::SUCCESS;
        }

        $uriAction = select(
            label: 'Selecciona una acción',
            options: collect($actions)->mapWithKeys(fn($a) => [
                $a['uri_action'] => "{$a['name']} - {$a['description']}",
            ])->toArray()
        );

        $actionDetail = $this->actionApi->show($uriAction);

        $associatedRealms = $associatedRealms = collect($actionDetail['realms'] ?? []);

        if (!empty($associatedRealms)) {
            info('Entornos ya asociados:');
            table(
                headers: ['uri_realm', 'Name'],
                rows: $associatedRealms
                    ->map(fn($r) => [
                        $r['uri_realm'],
                        $r['name'] ?? '-',
                    ])
                    ->toArray()
            );
        }

        $associatedRealmUris = $associatedRealms->pluck('uri_realm')->toArray();

        $realms = $this->realmApi->index(true);

        if (empty($realms)) {
            warning('No hay entornos disponibles.');
            return self::SUCCESS;
        }

        $availableRealms = collect($realms)
            ->reject(
                fn($realm) =>
                in_array($realm['uri_realm'], $associatedRealmUris)
            );

        if ($availableRealms->isEmpty()) {
            info('Todos los entornos ya están asociados a esta acción.');
            return self::SUCCESS;
        }

        $selectedRealms = multiselect(
            label: 'Selecciona los entornos a asociar',
            options: $availableRealms->mapWithKeys(fn($r) => [
                $r['uri_realm'] => $r['name'],
            ])->toArray()
        );

        if (empty($selectedRealms)) {
            warning('No seleccionaste ningún entorno.');
            return self::SUCCESS;
        }

        if (!confirm('¿Deseas asociar los entornos seleccionados a esta acción?')) {
            info('Operación cancelada.');
            return self::SUCCESS;
        }

        $realms = $this->actionApi->addRealms($uriAction, [
            'realms' => array_values($selectedRealms),
        ]);

        if ($realms === false) {
            warning('Error al asociar los entornos.');
            return self::FAILURE;
        }
        info('Entornos asociados correctamente.');
        return self::SUCCESS;
    }
}
