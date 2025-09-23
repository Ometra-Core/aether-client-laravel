<?php

namespace Ometra\AetherClient\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Ometra\AetherClient\AetherClient;

class ReportAether extends Command
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

        if (empty($data)) {
            $data = null;
        }

        $decodedData = $data;

        if (is_string($data)) {
            if ($this->isJson($data)) {
                $decodedData = json_decode($data, true);
            } else {
                $this->error('The value of data is not a valid JSON.');
                return 1;
            }
        }

        try {
            $client = new AetherClient();
            $client->report($action, $decodedData);
            $this->info("Report sent with action:: {$action}, payload: " . json_encode($decodedData));
        } catch (Exception $e) {
            $this->error("Error sending report: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
