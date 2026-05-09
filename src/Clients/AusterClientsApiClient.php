<?php

namespace Ometra\HelaSdk\Clients;

use Illuminate\Http\Client\Response;
use Ometra\HelaSdk\Dtos\AddressDto;
use Ometra\HelaSdk\Dtos\ApiResponseDto;
use Ometra\HelaSdk\Dtos\AuthTokenDto;
use Ometra\HelaSdk\Dtos\DtoCollection;
use Ometra\HelaSdk\Dtos\GenericDto;
use Ometra\HelaSdk\Dtos\OfferDto;
use Ometra\HelaSdk\Dtos\OrderDto;
use Ometra\HelaSdk\Dtos\ServiceDto;
use Ometra\HelaSdk\Dtos\UserProfileDto;

class AusterClientsApiClient extends HelaAppClient
{
    public function token(): ?string
    {
        $token = parent::token();

        if ($token === null) {
            return null;
        }

        if (preg_match('/^[A-Z]+-(.+)$/', $token, $matches) === 1) {
            $token = $matches[1];
        }

        return $this->tokenType() . '-' . $token;
    }

    public function tokenType(): string
    {
        $type = $this->config()['token_type'] ?? 'API';

        return is_string($type) && $type !== '' ? strtoupper($type) : 'API';
    }

    /**
     * @param array<string, mixed> $data
     */
    public function login(array $data): AuthTokenDto
    {
        return $this->dto($this->postWithoutToken('/clients-api/authentication/login', $data), AuthTokenDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function signup(array $data): GenericDto
    {
        return $this->dto($this->postWithoutToken('/clients-api/authentication/signup', $data), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function requestPasswordReset(array $data): ApiResponseDto
    {
        return $this->apiResponse($this->postWithoutToken('/clients-api/authentication/password/reset', $data));
    }

    public function validatePasswordResetToken(string $token): GenericDto
    {
        return $this->dto($this->httpWithoutToken()->get('/clients-api/authentication/password/reset/' . $token), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function resetPassword(string $token, array $data): ApiResponseDto
    {
        return $this->apiResponse($this->postWithoutToken('/clients-api/authentication/password/reset/' . $token, $data));
    }

    public function logout(): ApiResponseDto
    {
        return $this->apiResponse($this->get('/clients-api/authentication/logout'));
    }

    public function logoutAll(): ApiResponseDto
    {
        return $this->apiResponse($this->get('/clients-api/authentication/logout-all'));
    }

    public function clientProfile(): UserProfileDto
    {
        return $this->dto($this->get('/clients-api/client-profile'), UserProfileDto::class);
    }

    public function userProfile(): UserProfileDto
    {
        return $this->dto($this->get('/clients-api/user-profile'), UserProfileDto::class);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<GenericDto>
     */
    public function simCards(array $query = []): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/sim-cards', $query), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function balance(array $query = []): GenericDto
    {
        return $this->dto($this->get('/clients-api/accounting/balance', $query), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<GenericDto>
     */
    public function invoices(array $query = []): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/accounting/invoices', $query), GenericDto::class);
    }

    public function invoice(int|string $invoiceId): GenericDto
    {
        return $this->dto($this->get('/clients-api/accounting/invoices/' . $invoiceId), GenericDto::class);
    }

    public function downloadInvoice(int|string $invoiceId): Response
    {
        return $this->get('/clients-api/accounting/invoices/download/' . $invoiceId);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<AddressDto>
     */
    public function addresses(array $query = []): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/addresses', $query), AddressDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createAddress(array $data): AddressDto|ApiResponseDto
    {
        $response = $this->post('/clients-api/addresses', $data);
        $data = $this->responseData($response);

        return is_array($data) ? AddressDto::from($data) : $this->apiResponse($response);
    }

    public function address(int|string $addressId): AddressDto
    {
        return $this->dto($this->get('/clients-api/addresses/' . $addressId), AddressDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateAddress(int|string $addressId, array $data): AddressDto
    {
        return $this->dto($this->post('/clients-api/addresses/' . $addressId, $data), AddressDto::class);
    }

    public function deleteAddress(int|string $addressId): ApiResponseDto
    {
        return $this->apiResponse($this->delete('/clients-api/addresses/' . $addressId));
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<OfferDto>
     */
    public function catalogOffers(array $query = []): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/catalogs/offers', $query), OfferDto::class);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<GenericDto>
     */
    public function cfdi(array $query = []): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/cfdi', $query), GenericDto::class);
    }

    /**
     * @return DtoCollection<OrderDto>
     */
    public function cfdiOrders(): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/cfdi/orders'), OrderDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function requestCfdi(array $data): ApiResponseDto
    {
        return $this->apiResponse($this->post('/clients-api/cfdi/request', $data));
    }

    public function downloadCfdi(string $uid, string $format): Response
    {
        return $this->get('/clients-api/cfdi/download/' . $uid . '/' . $format);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<OrderDto>
     */
    public function orders(array $query = []): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/orders', $query), OrderDto::class);
    }

    public function order(int|string $orderId): OrderDto
    {
        return $this->dto($this->get('/clients-api/orders/' . $orderId), OrderDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createOrder(array $data): OrderDto
    {
        return $this->dto($this->post('/clients-api/orders', $data), OrderDto::class);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<GenericDto>
     */
    public function portabilities(array $query = []): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/portability', $query), GenericDto::class);
    }

    public function portability(int|string $portabilityId): GenericDto
    {
        return $this->dto($this->get('/clients-api/portability/' . $portabilityId), GenericDto::class);
    }

    /**
     * @return DtoCollection<GenericDto>
     */
    public function portabilityTransitories(): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/portability/transitories'), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function requestPortability(array $data): GenericDto
    {
        return $this->dto($this->post('/clients-api/portability/request', $data), GenericDto::class);
    }

    public function deletePortability(int|string $portabilityId): ApiResponseDto
    {
        return $this->apiResponse($this->delete('/clients-api/portability/' . $portabilityId));
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<ServiceDto>
     */
    public function services(array $query = []): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/services', $query), ServiceDto::class);
    }

    public function service(string $msisdn): ServiceDto
    {
        return $this->dto($this->get('/clients-api/services/' . $msisdn), ServiceDto::class);
    }

    public function serviceProfile(string $msisdn): GenericDto
    {
        return $this->dto($this->get('/clients-api/services/' . $msisdn . '/profile'), GenericDto::class);
    }

    /**
     * @return DtoCollection<GenericDto>
     */
    public function serviceBags(string $msisdn): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/services/' . $msisdn . '/bags'), GenericDto::class);
    }

    /**
     * @return DtoCollection<OfferDto>
     */
    public function activateOptions(string $msisdn): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/services/' . $msisdn . '/activate-options'), OfferDto::class);
    }

    /**
     * @return DtoCollection<OfferDto>
     */
    public function topupOptions(string $msisdn): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/services/' . $msisdn . '/topup-options'), OfferDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function activateService(string $msisdn, array $data = []): OrderDto
    {
        return $this->dto($this->post('/clients-api/services/' . $msisdn . '/activate', $data), OrderDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function topupService(string $msisdn, array $data): OrderDto
    {
        return $this->dto($this->post('/clients-api/services/' . $msisdn . '/topup', $data), OrderDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateServiceName(string $msisdn, array $data): ApiResponseDto
    {
        return $this->apiResponse($this->post('/clients-api/services/' . $msisdn . '/update-name', $data));
    }

    public function suspendService(string $msisdn): ApiResponseDto
    {
        return $this->apiResponse($this->post('/clients-api/services/' . $msisdn . '/suspend'));
    }

    public function resumeService(string $msisdn): ApiResponseDto
    {
        return $this->apiResponse($this->post('/clients-api/services/' . $msisdn . '/resume'));
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<UserProfileDto>
     */
    public function users(array $query = []): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/users', $query), UserProfileDto::class);
    }

    public function user(string $clientUserUri): UserProfileDto
    {
        return $this->dto($this->get('/clients-api/users/' . $clientUserUri), UserProfileDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createUser(array $data): UserProfileDto
    {
        return $this->dto($this->post('/clients-api/users', $data), UserProfileDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateUser(string $clientUserUri, array $data): UserProfileDto
    {
        return $this->dto($this->put('/clients-api/users/' . $clientUserUri, $data), UserProfileDto::class);
    }

    public function deleteUser(string $clientUserUri): ApiResponseDto
    {
        return $this->apiResponse($this->delete('/clients-api/users/' . $clientUserUri));
    }
}
