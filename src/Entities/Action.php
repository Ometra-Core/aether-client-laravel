<?php

namespace Ometra\AetherClient\Entities;

use Ometra\AetherClient\ApiClient;

class Action
{
    public function  __construct(protected ApiClient $apiClient)
    {
        //
    }

    public function index(): array|bool
    {
        $response = $this->apiClient->get(
            "/applications/{$this->apiClient->getUriApplication()}/actions",
            [
                'command' => true,
            ]
        );

        return $response;
    }

    public function create(array $data): bool
    {
        $response = $this->apiClient->post("/applications/{$this->apiClient->getUriApplication()}/actions", $data);
        return $response !== false;
    }

    public function update(string $uriAction, array $data): bool
    {
        $response = $this->apiClient->put("/applications/{$this->apiClient->getUriApplication()}/actions/{$uriAction}/update", $data);
        return $response !== false;
    }

    public function delete(string $uriAction): bool
    {
        $response = $this->apiClient->delete("/applications/{$this->apiClient->getUriApplication()}/actions/{$uriAction}/destroy");
        return $response !== false;
    }

    public function getSetting(string $uriAction)
    {
        $response = $this->apiClient->get("/applications/{$this->apiClient->getUriApplication()}/actions/{$uriAction}/realm-action-setting", [
            'uri_realm' => $this->apiClient->getRealmId(),
        ]);
        return $response;
    }

    public function updateSetting(string $uriAction, array $data): bool
    {
        $response = $this->apiClient->post("/applications/{$this->apiClient->getUriApplication()}/actions/{$uriAction}/update-realm-action-setting", $data);
        return $response !== false;
    }

    public function addRealms(string $uriAction, array $data): bool
    {
        $response = $this->apiClient->post("/applications/{$this->apiClient->getUriApplication()}/actions/{$uriAction}/associate-realm", $data);

        return $response !== false;
    }

    public function show(string $uriAction)
    {
        $response = $this->apiClient->get("/applications/{$this->apiClient->getUriApplication()}/actions/{$uriAction}");
        return $response;
    }
}
