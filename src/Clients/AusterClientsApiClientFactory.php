<?php

namespace Ometra\HelaSdk\Clients;

use Ometra\HelaSdk\HelaSdk;

final class AusterClientsApiClientFactory
{
    public function __construct(private readonly HelaSdk $sdk)
    {
    }

    public function forClient(): AusterClientsApiClient
    {
        return $this->sdk->auster()->clientsApi();
    }

    public function forUser(string $token): AusterClientsApiClient
    {
        return $this->sdk->auster()->clientsApiAsUser($token);
    }
}
