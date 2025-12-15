<?php

namespace Ometra\AetherClient\Entities;

use Ometra\AetherClient\ApiClient;

class Realm
{

    public function  __construct(protected ApiClient $apiClient) 
    {
        //
    }

    public function index()
    {
        $response = $this->apiClient->get("/applications/{$this->apiClient->getUriApplication()}/realms");
        return $response;
    }

    public function create(array $data)
    {
        $response = $this->apiClient->post("/applications/{$this->apiClient->getUriApplication()}/realms", $data);
        return $response;
    }

    public function update(string $uriRealm, array $data)
    {
        $response = $this->apiClient->put("/applications/{$this->apiClient->getUriApplication()}/realms/{$uriRealm}/update", $data);
        return $response;
    }
}
