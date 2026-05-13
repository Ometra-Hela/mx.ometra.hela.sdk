<?php

namespace Ometra\HelaSdk\Clients;

use Ometra\HelaSdk\Dtos\ApiResponseDto;
use Ometra\HelaSdk\Dtos\DtoCollection;
use Ometra\HelaSdk\Dtos\GenericDto;
use Ometra\HelaSdk\Dtos\OfferDto;
use Ometra\HelaSdk\Dtos\OrderDto;
use Ometra\HelaSdk\Dtos\PaymentDto;
use Ometra\HelaSdk\Dtos\ServiceDto;

class AusterClient extends HelaAppClient
{
    private ?AusterClientsApiClient $clientsApi = null;

    public function clientsApi(?string $token = null, ?string $tokenType = null): AusterClientsApiClient
    {
        if ($token === null && $tokenType === null && $this->clientsApi instanceof AusterClientsApiClient) {
            return $this->clientsApi;
        }

        $config = is_array($this->config()['clients_api'] ?? null) ? $this->config()['clients_api'] : [];
        $config['base_url'] ??= $this->config()['base_url'] ?? null;

        if ($token !== null) {
            $config['token'] = $token;
        }

        if ($tokenType !== null) {
            $config['token_type'] = $tokenType;
        }

        $client = new AusterClientsApiClient('auster.clients-api', $config, $this->config());

        if ($token === null && $tokenType === null) {
            $this->clientsApi = $client;
        }

        return $client;
    }

    public function clientsApiAsUser(string $token): AusterClientsApiClient
    {
        return $this->clientsApi($token, 'USR');
    }

    public function clientsApiAsClient(string $token): AusterClientsApiClient
    {
        return $this->clientsApi($token, 'API');
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<OfferDto>
     */
    public function offers(array $query = []): DtoCollection
    {
        return $this->dtoCollection($this->get('/api/catalogs/offers', $query), OfferDto::class);
    }

    public function offer(int|string $offerId): OfferDto
    {
        return $this->dto($this->get('/api/catalogs/offers/' . $offerId), OfferDto::class, 'offer');
    }

    public function portabilitiesByMsisdn(string $msisdn): GenericDto
    {
        return $this->dto($this->get('/api/catalogs/portability/msisdn/' . $msisdn), GenericDto::class);
    }

    public function serviceByMsisdn(string $msisdn): ServiceDto
    {
        return $this->dto($this->get('/api/services/msisdn/' . $msisdn), ServiceDto::class);
    }

    /**
     * @return DtoCollection<OfferDto>
     */
    public function serviceSupplementaries(string $msisdn): DtoCollection
    {
        return $this->dtoCollection($this->get('/api/services/msisdn/' . $msisdn . '/supplementaries'), OfferDto::class);
    }

    /**
     * @return DtoCollection<OfferDto>
     */
    public function serviceReplacements(string $msisdn): DtoCollection
    {
        return $this->dtoCollection($this->get('/api/services/msisdn/' . $msisdn . '/replacements'), OfferDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validateActivationKey(array $data): GenericDto
    {
        return $this->dto($this->post('/api/services/activations/validate/activation-key', $data), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validateSimCard(array $data): GenericDto
    {
        return $this->dto($this->post('/api/services/activations/validate/sim-card', $data), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function activateService(array $data): GenericDto
    {
        return $this->dto($this->post('/api/services/activations/activate', $data), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createOrder(array $data): OrderDto
    {
        return $this->dto($this->post('/api/orders/new', $data), OrderDto::class);
    }

    public function order(int|string $orderId): OrderDto
    {
        return $this->dto($this->get('/api/orders/' . $orderId), OrderDto::class);
    }

    /**
     * @return DtoCollection<OrderDto>
     */
    public function orderByMsisdn(string $msisdn): DtoCollection
    {
        return $this->dtoCollection($this->get('/api/orders/msisdn/' . $msisdn), OrderDto::class);
    }

    /**
     * @return DtoCollection<PaymentDto>
     */
    public function orderPayment(int|string $orderId): DtoCollection
    {
        return $this->dtoCollection($this->get('/api/orders/' . $orderId . '/payment'), PaymentDto::class);
    }

    public function publishOrder(int|string $orderId): OrderDto
    {
        return $this->dto($this->post('/api/orders/' . $orderId . '/publish'), OrderDto::class);
    }

    public function processOrder(int|string $orderId): ApiResponseDto
    {
        return $this->apiResponse($this->post('/api/orders/' . $orderId . '/process'));
    }

    public function cancelOrder(int|string $orderId): ApiResponseDto
    {
        return $this->apiResponse($this->post('/api/orders/' . $orderId . '/cancel'));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function addOrderPayment(int|string $orderId, array $data): ApiResponseDto
    {
        return $this->apiResponse($this->post('/api/orders/' . $orderId . '/add-payment', $data));
    }

    public function validatePayment(int|string $paymentId): ApiResponseDto
    {
        return $this->apiResponse($this->post('/api/payments/' . $paymentId . '/validate'));
    }

    public function cancelPayment(int|string $paymentId): ApiResponseDto
    {
        return $this->apiResponse($this->post('/api/payments/' . $paymentId . '/cancel'));
    }
}
