<?php

namespace Ometra\HelaSdk\Clients;

use Ometra\HelaSdk\Dtos\ApiResponseDto;
use Ometra\HelaSdk\Dtos\DtoCollection;
use Ometra\HelaSdk\Dtos\GenericDto;
use Ometra\HelaSdk\Dtos\OfferDto;
use Ometra\HelaSdk\Dtos\OrderDto;
use Ometra\HelaSdk\Dtos\OrderItemDto;
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

        $client = new AusterClientsApiClient('auster.clients-api', $config, $this->defaults());

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
    public function offers(
        array $query = [],
        string|int|float|null $filter = null,
        string|int|bool|null $status = null,
        ?string $product = null,
        array|string|null $serviceType = null,
        ?bool $allowsNewLineActivation = null,
    ): DtoCollection {
        return $this->dtoCollection(
            $this->get('/api/catalogs/offers', $this->mergeQuery($query, compact(
                'filter',
                'status',
                'product',
                'serviceType',
                'allowsNewLineActivation',
            ))),
            OfferDto::class,
        );
    }

    public function offer(int|string $offerId): OfferDto
    {
        return $this->dto($this->get('/api/catalogs/offers/' . $offerId), OfferDto::class, 'offer');
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<GenericDto>
     */
    public function clients(
        array $query = [],
        string|int|float|null $filter = null,
        ?string $type = null,
    ): DtoCollection {
        return $this->dtoCollection(
            $this->get('/api/clients', $this->mergeQuery($query, compact('filter', 'type'))),
            GenericDto::class,
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    public function clientVerification(array $query = []): GenericDto
    {
        return $this->dto($this->get('/api/clients/verification', $query), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function generateClientVerificationCode(array $data): ApiResponseDto
    {
        return $this->apiResponse($this->post('/api/clients/generate-code', $data));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function confirmClientVerificationCode(array $data): ApiResponseDto
    {
        return $this->apiResponse($this->post('/api/clients/confirm-code', $data));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function generateClientVerificationTicket(array $data): ApiResponseDto
    {
        return $this->apiResponse($this->post('/api/clients/generate-ticket', $data));
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<ServiceDto>
     */
    public function clientServices(
        int|string $clientId,
        array $query = [],
        string|int|float|null $filter = null,
        array|string|null $product = null,
        array|string|null $serviceType = null,
        array|string|null $status = null,
        int|string|null $groupId = null,
        ?string $sort = null,
        ?string $direction = null,
    ): DtoCollection {
        return $this->dtoCollection(
            $this->get('/api/clients/' . $clientId . '/services', $this->mergeQuery($query, compact(
                'filter',
                'product',
                'serviceType',
                'status',
                'groupId',
                'sort',
                'direction',
            ))),
            ServiceDto::class,
        );
    }

    public function portabilitiesByMsisdn(string $msisdn): GenericDto
    {
        return $this->dto($this->get('/api/catalogs/portability/msisdn/' . $msisdn), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<GenericDto>
     */
    public function portabilities(array $query = []): DtoCollection
    {
        return $this->clientsApi()->portabilities($query);
    }

    public function portability(int|string $portabilityId): GenericDto
    {
        return $this->clientsApi()->portability($portabilityId);
    }

    /**
     * @return DtoCollection<GenericDto>
     */
    public function portabilityTransitories(): DtoCollection
    {
        return $this->clientsApi()->portabilityTransitories();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validatePortability(array $data): GenericDto
    {
        return $this->clientsApi()->validatePortability($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function requestPortability(array $data): GenericDto
    {
        return $this->clientsApi()->requestPortability($data);
    }

    public function deletePortability(int|string $portabilityId): ApiResponseDto
    {
        return $this->clientsApi()->deletePortability($portabilityId);
    }

    public function serviceByMsisdn(string $msisdn): ServiceDto
    {
        return $this->dto($this->get('/api/services/msisdn/' . $msisdn), ServiceDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function supportSuspendServiceForTheft(string $msisdn, array $data = []): ApiResponseDto
    {
        return $this->apiResponse($this->post('/api/support/services/' . rawurlencode($msisdn) . '/suspend-stolen', $data));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function supportLockImei(string $imei, array $data = []): ApiResponseDto
    {
        return $this->apiResponse($this->post('/api/support/imei/' . rawurlencode($imei) . '/lock', $data));
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
        return $this->dto($this->post('/api/orders', $data), OrderDto::class);
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
        return $this->dtoCollection($this->get('/api/orders', ['msisdn' => $msisdn]), OrderDto::class);
    }

    /**
     * @return DtoCollection<PaymentDto>
     */
    public function orderPayment(int|string $orderId): DtoCollection
    {
        return $this->dtoCollection($this->get('/api/orders/' . $orderId . '/payments'), PaymentDto::class);
    }

    public function publishOrder(int|string $orderId): OrderDto
    {
        return $this->dto($this->put('/api/orders/' . $orderId . '/publication', ['published' => true]), OrderDto::class);
    }

    public function unpublishOrder(int|string $orderId): OrderDto
    {
        return $this->dto($this->put('/api/orders/' . $orderId . '/publication', ['published' => false]), OrderDto::class);
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
        return $this->apiResponse($this->post('/api/orders/' . $orderId . '/payments', $data));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setOrderDiscountCode(int|string $orderId, array $data): ApiResponseDto
    {
        if (!array_key_exists('discount_code', $data) && array_key_exists('code', $data)) {
            $data['discount_code'] = $data['code'];
            unset($data['code']);
        }

        return $this->apiResponse($this->put('/api/orders/' . $orderId . '/discount-code', $data));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function addOrderItem(int|string $orderId, array $data): OrderItemDto
    {
        return $this->dto($this->post('/api/orders/' . $orderId . '/items', $data), OrderItemDto::class, 'item');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function bulkCreateOrderItems(int|string $orderId, array $data): GenericDto
    {
        return $this->dto($this->post('/api/orders/' . $orderId . '/items/bulk', $data), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function bulkAssignOrderItemTargets(int|string $orderId, array $data): GenericDto
    {
        return $this->dto($this->patch('/api/orders/' . $orderId . '/items/targets', $data), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateOrderItem(int|string $orderId, int|string $orderItemId, array $data): OrderItemDto
    {
        return $this->dto($this->patch('/api/orders/' . $orderId . '/items/' . $orderItemId, $data), OrderItemDto::class, 'item');
    }

    public function removeOrderItem(int|string $orderId, int|string $orderItemId): ApiResponseDto
    {
        return $this->apiResponse($this->delete('/api/orders/' . $orderId . '/items/' . $orderItemId));
    }

    public function validatePayment(int|string $paymentId): ApiResponseDto
    {
        return $this->apiResponse($this->post('/api/payments/' . $paymentId . '/validate'));
    }

    public function cancelPayment(int|string $paymentId): ApiResponseDto
    {
        return $this->apiResponse($this->post('/api/payments/' . $paymentId . '/cancel'));
    }

    public function validateDiscountCode(string $code): GenericDto
    {
        return $this->dto($this->get('/api/discounts/validate/' . rawurlencode($code)), GenericDto::class);
    }

    public function imeiCompatibility(string $imei): GenericDto
    {
        return $this->dto($this->get('/api/tools/imei/' . rawurlencode($imei) . '/compatibility'), GenericDto::class);
    }

    public function searchMsisdns(array $data): ApiResponseDto
    {
        return $this->apiResponse($this->get('/api/vinculacion/consulta', $data));
    }

    public function validateVinculacionMsisdns(array $data): ApiResponseDto
    {
        return $this->apiResponse($this->post('/api/vinculacion/msisdn/validate', $data));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validateGiftCard(array $data): GenericDto
    {
        return $this->dto($this->post('/api/services/gift-cards/validate', $data), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function redeemGiftCard(array $data): GenericDto
    {
        return $this->dto($this->post('/api/services/gift-cards/redeem', $data), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validatePersonaFisica(array $data): ApiResponseDto
    {
        return $this->apiResponse($this->postMultipart('/api/vinculacion/persona-fisica/validate', $data));
    }

    public function vinculaPersonaFisica(array $data): ApiResponseDto
    {
        return $this->apiResponse($this->postMultipart('/api/vinculacion/persona-fisica/vincula', $data));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function desvinculaPersonaFisica(array $data): ApiResponseDto
    {
        return $this->apiResponse($this->postMultipart('/api/vinculacion/persona-fisica/desvincula', $data));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function testPersonaFisica(array $data): ApiResponseDto
    {
        return $this->apiResponse($this->postMultipart('/api/vinculacion/persona-fisica/test', $data));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validatePersonaMoral(array $data): ApiResponseDto
    {
        return $this->apiResponse($this->postMultipart('/api/vinculacion/persona-moral/validate', $data));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function registerPersonaMoral(array $data): ApiResponseDto
    {
        return $this->apiResponse($this->postMultipart('/api/vinculacion/persona-moral/register', $data));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function vinculaPersonaMoral(array $data): ApiResponseDto
    {
        return $this->apiResponse($this->postMultipart('/api/vinculacion/persona-moral/vincula', $data));
    }

    /**
     * @return DtoCollection<GenericDto>
     */
    public function zephyrRunnerInstances(): DtoCollection
    {
        return $this->dtoCollection($this->get('/api/zephyr-runner/instances'), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateZephyrRunnerInstanceStatus(array $data): ApiResponseDto
    {
        return $this->apiResponse($this->post('/api/zephyr-runner/instances/status', $data));
    }
}
