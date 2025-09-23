<?php

namespace Ometra\AetherClient\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Ometra\AetherClient\Facades\AetherClient;

class AetherReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aether:report {action} {--data=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Report an action to the Aether service';

    public function handle()
    {
        $action = $this->argument('action');
        $data = $this->option('data');

        $decodedData = null;

        if ($data) {
            $decodedData = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("Invalid JSON data provided.");
                return 1;
            }
        }

        $response = AetherClient::report($action, $decodedData);
        if (isset($response['status']) && $response['status'] === 'error') {
            $this->error("Server responded with error: " . ($response['message'] ?? 'Unknown error'));
            return 1;
        }

        $message = $response['message'] ?? 'No message returned';
        $this->info("Report sent with action: {$action}, payload: " . json_encode($decodedData));
        $this->info("Server response: " . $message);
    }
}
