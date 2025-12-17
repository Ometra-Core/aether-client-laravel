<?php

namespace Ometra\AetherClient\Console\Commands\Actions;

use Ometra\AetherClient\Entities\Action;
use Illuminate\Console\Command;
use function Laravel\Prompts\{info, table, warning, error};

class Actions extends Command
{
    protected $signature = 'aether:actions';
    protected $description = 'List the actions that the application has';

    public function __construct(protected Action $actionApi)
    {
        parent::__construct();
    }

    public function handle()
    {
        $actions = $this->actionApi->index();

        if (empty($actions)) {
            warning('No hay acciones registradas.');
            return self::SUCCESS;
        }

        if ($actions === false) {
            error('Error al obtener las acciones.');
            return self::FAILURE;
        }

        info('<fg=cyan>Lista de acciones</>');
        table(
            headers: ['#', 'Name', 'Description', 'uri_action'],
            rows: collect($actions)->values()->map(fn($a, $i) => [
                $i + 1,
                str_pad($a['name'], 15),
                str_pad($a['description'] ?? '-', 30),
                $a['uri_action'],
            ])->toArray()
        );
    }
}
