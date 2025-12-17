<?php

namespace Ometra\AetherClient\Console\Commands\Actions;

use Illuminate\Console\Command;
use Ometra\AetherClient\Entities\Action;
use function Laravel\Prompts\{select, confirm, info, warning};

class DeleteAction extends Command
{
    protected $signature = 'aether:delete-action';
    protected $description = 'Delete a specific action by its URI';

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

        $uriAction = select(
            label: 'Selecciona una acción para eliminar:',
            options: collect($actions)->mapWithKeys(fn($a) => [
                $a['uri_action'] => "{$a['name']} - {$a['description']}",
            ])->toArray()
        );

        $confirmed = confirm(
            label: "¿Estás seguro de que deseas eliminar la acción seleccionada?"
        );

        if (!$confirmed) {
            $this->info('Operación cancelada.');
            return self::SUCCESS;
        }

        $delete=$this->actionApi->delete($uriAction);

        if ($delete === true) {
            info('Acción eliminada correctamente.');
            return self::SUCCESS;
        } else {
            warning('Error al eliminar la acción.');
            return self::FAILURE;
        }
    }
}
