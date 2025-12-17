<?php

namespace Ometra\AetherClient\Console\Commands\Realms;

use Ometra\AetherClient\Console\BaseCommands;
use Ometra\AetherClient\Entities\Realm;
use Illuminate\Console\Command;

class Realms extends Command
{
    protected $signature = 'aether:realms';
    protected $description = 'List the realms that the application has';

    public function __construct(protected Realm $realmApi)
    {
        parent::__construct();
    }

    public function handle()
    {
        $realms = $this->realmApi->index();

        if (empty($realms)) {
            $this->warning('No hay realms registrados.');
            return self::SUCCESS;
        }

        if ($realms === false) {
            $this->error('Error al obtener los realms.');
            return self::FAILURE;
        }

        $this->info('<fg=cyan>Lista de realms</>');
        $this->table(
            headers: ['#', 'Name', 'uri_realm'],
            rows: collect($realms)->values()->map(fn($r, $i) => [
                $i + 1,
                str_pad($r['name'], 15),
                $r['uri_realm'],
            ])->toArray()
        );
    }
}
