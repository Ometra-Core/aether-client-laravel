<?php

namespace Ometra\AetherClient\Entities;

use Ometra\AetherClient\ApiClient;

class Realm
{

    public function  __construct(protected ApiClient $apiClient)
    {
        //
    }

    public function index(): array|bool
    {
        $response = $this->apiClient->get("/applications/{$this->apiClient->getUriApplication()}/realms", [
            'command' => true,
        ]);
        return $response;
    }

    public function create(array $data): bool
    {
        $response = $this->apiClient->post("/applications/{$this->apiClient->getUriApplication()}/realms", $data);
        return $response !== false;
    }

    public function update(string $uriRealm, array $data): bool
    {
        $response = $this->apiClient->put("/applications/{$this->apiClient->getUriApplication()}/realms/{$uriRealm}/update", $data);
        return $response !== false;
    }
}
