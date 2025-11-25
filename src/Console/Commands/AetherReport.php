<?php

namespace Ometra\AetherClient\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Ometra\AetherClient\Facades\AetherClient;
use Illuminate\Support\Facades\Log;

class AetherReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aether:report {action} {status?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Report an action to the Aether service';

    public function handle()
    {
        $action = $this->argument('action');
        $status = $this->argument('status');
        $log_level = strtolower(config('aether-client.log_level'));

        $response = AetherClient::report($action, $status);
        $message = $response['message'] ?? 'No message returned';
        if (isset($response['status']) && $response['status'] === 'error') {
            Log::channel('aether')->error("Server responded with error: " . $message);
            $this->error("Server responded with error: " . $message);
            return 1;
        }

        if ($log_level === 'debug') {
            Log::channel('aether')->info("Report sent with action: {$action}");
        }
        $this->info("Report sent with action: {$action}");
        $this->info("Server response: " . $message);
        return 0;
    }
}
