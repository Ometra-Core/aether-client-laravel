<?php

namespace Ometra\AetherClient\Console\Commands\Actions;

use Ometra\AetherClient\Console\BaseCommands;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class DeleteAction extends BaseCommands
{
    protected $signature = 'aether:delete-action';
    protected $description = 'Delete a specific action by its URI';

    public function handle()
    {
        try {
            $baseUrl = $this->base_url;
            $token = $this->token;
            $uriApplication = $this->getUriApplication();
            $logLevel = $this->log_level;

            if (!$uriApplication) {
                $this->error("No se pudo obtener la URI de la aplicación desde el token.");
            }

            $url = "{$baseUrl}/applications/{$uriApplication}/actions";
            $response = Http::withToken($token)->withHeaders([
                'Accept' => 'application/json',
            ])->get($url);

            if (!$response->ok()) {
                $this->error("No se pudieron obtener las acciones.");
                return 1;
            }

            $actions = $response->json()['data'] ?? [];

            if (empty($actions)) {
                $this->warn("No hay acciones registradas.");
                return 0;
            }

            $choices = [];
            foreach ($actions as $action) {
                $label = "{$action['name']} - {$action['description']}";
                $choices[$label] = $action['uri_action'];
            }

            $selectedLabel = $this->choice("Selecciona una acción para eliminar:", array_keys($choices));
            $selectedUri = $choices[$selectedLabel];
            $selectedAction = collect($actions)->firstWhere('uri_action', $selectedUri);
            $uriAction = $selectedAction['uri_action'] ?? null;

            if (!$selectedAction) {
                $this->error("Acción no encontrada.");
                return 1;
            }

            $this->info("Has seleccionado: {$selectedAction['name']}");
            if (!$this->confirm("¿Deseas eliminar está aplicación?", true)) {
                $this->info("No se realizaron cambios.");
                return 0;
            }

            $url = "{$baseUrl}/applications/{$uriApplication}/actions/$uriAction/destroy";

            $response = Http::withToken($token)->withHeaders([
                'Accept' => 'application/json',
            ])->delete($url);

            if (!$response->ok()) {
                $this->error("Error al eliminar la acción.");
                Log::channel('aether')->error("Request fallida a $url: " . $response->body());
                return 1;
            }

            $responseData = $response->json();

            $status = $responseData['status'] ?? null;
            $message = $responseData['message'] ?? 'Sin mensaje';

            if ($status !== 200) {
                $this->error("Error del servidor: $message");
                Log::channel('aether')->error("Respuesta con error desde $url: $message");
                return 1;
            }

            if ($logLevel === 'debug') {
                Log::channel('aether')->debug("Acción eliminada correctamente: {$selectedAction['name']} con URI: $uriAction");
                return 0;
            }
            $this->info("Acción eliminada correctamente: {$selectedAction['name']} con URI: $uriAction");
        } catch (Exception $e) {
            Log::channel('aether')->error("Excepción en aether:actions -> " . $e->getMessage());
            $this->error("Error inesperado: " . $e->getMessage());
            return 1;
        }
    }
}
