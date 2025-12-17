<?php

namespace Ometra\AetherClient\Console\Commands\Actions;

use Illuminate\Console\Command;
use Ometra\AetherClient\Entities\Action;
use function Laravel\Prompts\{select, confirm, info, warning};


class UpdateAction extends Command
{
    protected $signature = 'aether:update-action';
    protected $description = 'Update a specific action by its URI';

    public function __construct(protected Action $actionApi)
    {
        parent::__construct();
    }

    public function handle() {
        
    }
}
