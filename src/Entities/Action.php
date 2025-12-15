<?php

namespace Ometra\AetherClient\Entities;

use Ometra\AetherClient\ApiClient;

class Action
{
    public function  __construct(protected ApiClient $apiClient)
    {
        //
    }

    public function index()
    {
        $response = $this->apiClient->get("/applications/{$this->apiClient->getUriApplication()}/actions");
        return $response;
    }

    public function create(array $data)
    {
        $response = $this->apiClient->post("/applications/{$this->apiClient->getUriApplication()}/actions", $data);
        return $response;
    }

    public function update(string $uriAction, array $data)
    {
        $response = $this->apiClient->put("/applications/{$this->apiClient->getUriApplication()}/actions/{$uriAction}/update", $data);
        return $response;
    }

    public function delete(string $uriAction)
    {
        $response = $this->apiClient->delete("/applications/{$this->apiClient->getUriApplication()}/actions/{$uriAction}/destroy");
        return $response;
    }

    public function getSetting(string $uriAction)
    {
        $response = $this->apiClient->get("/applications/{$this->apiClient->getUriApplication()}/actions/{$uriAction}/realm-action-setting", [
            'uri_realm' => $this->apiClient->getRealmId(),
        ]);
        return $response;
    }

    public function updateSetting(string $uriAction, array $data)
    {
        $response = $this->apiClient->post("/applications/{$this->apiClient->getUriApplication()}/actions/{$uriAction}/update-realm-action-setting", $data);
        return $response;
    }
}
