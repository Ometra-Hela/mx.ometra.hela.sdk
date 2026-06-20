<?php

namespace Ometra\HelaSdk\Clients;

use Ometra\HelaSdk\Dtos\ApiResponseDto;
use Ometra\HelaSdk\Dtos\DtoCollection;
use Ometra\HelaSdk\Dtos\GenericDto;

class AlizeClient extends HelaAppClient
{
    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<GenericDto>
     */
    public function portabilities(array $query = []): DtoCollection
    {
        return $this->dtoCollection($this->get('/api/portabilities', $query), GenericDto::class);
    }

    public function portability(int|string $portabilityId): GenericDto
    {
        return $this->dto($this->get('/api/portabilities/' . $portabilityId), GenericDto::class);
    }

    public function portabilitiesByMsisdn(string $msisdn): GenericDto
    {
        return $this->dto($this->get('/api/portabilities/msisdn/' . rawurlencode($msisdn)), GenericDto::class);
    }

    /**
     * @return DtoCollection<GenericDto>
     */
    public function portabilityTransitories(): DtoCollection
    {
        return $this->dtoCollection($this->get('/api/portabilities/transitories'), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function requestPortability(array $data): GenericDto
    {
        return $this->dto($this->post('/api/portabilities/request', $data), GenericDto::class);
    }

    public function deletePortability(int|string $portabilityId): ApiResponseDto
    {
        return $this->apiResponse($this->delete('/api/portabilities/' . $portabilityId));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function executeAusterPortability(int|string $austerPortabilityId, array $data): ApiResponseDto
    {
        return $this->apiResponse($this->post(
            '/api/alize/portabilities/' . $austerPortabilityId . '/execute',
            $data,
        ));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function completeAusterPortability(int|string $austerPortabilityId, array $data): ApiResponseDto
    {
        return $this->apiResponse($this->post(
            '/api/alize/portabilities/' . $austerPortabilityId . '/complete',
            $data,
        ));
    }
}
