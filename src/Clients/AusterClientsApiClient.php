<?php

namespace Ometra\HelaSdk\Clients;

use Illuminate\Http\Client\Response;

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
    public function login(array $data): Response
    {
        return $this->postWithoutToken('/clients-api/authentication/login', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function signup(array $data): Response
    {
        return $this->postWithoutToken('/clients-api/authentication/signup', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function requestPasswordReset(array $data): Response
    {
        return $this->postWithoutToken('/clients-api/authentication/password/reset', $data);
    }

    public function validatePasswordResetToken(string $token): Response
    {
        return $this->httpWithoutToken()->get('/clients-api/authentication/password/reset/' . $token);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function resetPassword(string $token, array $data): Response
    {
        return $this->postWithoutToken('/clients-api/authentication/password/reset/' . $token, $data);
    }

    public function logout(): Response
    {
        return $this->get('/clients-api/authentication/logout');
    }

    public function logoutAll(): Response
    {
        return $this->get('/clients-api/authentication/logout-all');
    }

    public function clientProfile(): Response
    {
        return $this->get('/clients-api/client-profile');
    }

    public function userProfile(): Response
    {
        return $this->get('/clients-api/user-profile');
    }

    /**
     * @param array<string, mixed> $query
     */
    public function simCards(array $query = []): Response
    {
        return $this->get('/clients-api/sim-cards', $query);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function balance(array $query = []): Response
    {
        return $this->get('/clients-api/accounting/balance', $query);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function invoices(array $query = []): Response
    {
        return $this->get('/clients-api/accounting/invoices', $query);
    }

    public function invoice(int|string $invoiceId): Response
    {
        return $this->get('/clients-api/accounting/invoices/' . $invoiceId);
    }

    public function downloadInvoice(int|string $invoiceId): Response
    {
        return $this->get('/clients-api/accounting/invoices/download/' . $invoiceId);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function addresses(array $query = []): Response
    {
        return $this->get('/clients-api/addresses', $query);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createAddress(array $data): Response
    {
        return $this->post('/clients-api/addresses', $data);
    }

    public function address(int|string $addressId): Response
    {
        return $this->get('/clients-api/addresses/' . $addressId);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateAddress(int|string $addressId, array $data): Response
    {
        return $this->post('/clients-api/addresses/' . $addressId, $data);
    }

    public function deleteAddress(int|string $addressId): Response
    {
        return $this->delete('/clients-api/addresses/' . $addressId);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function catalogOffers(array $query = []): Response
    {
        return $this->get('/clients-api/catalogs/offers', $query);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function cfdi(array $query = []): Response
    {
        return $this->get('/clients-api/cfdi', $query);
    }

    public function cfdiOrders(): Response
    {
        return $this->get('/clients-api/cfdi/orders');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function requestCfdi(array $data): Response
    {
        return $this->post('/clients-api/cfdi/request', $data);
    }

    public function downloadCfdi(string $uid, string $format): Response
    {
        return $this->get('/clients-api/cfdi/download/' . $uid . '/' . $format);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function orders(array $query = []): Response
    {
        return $this->get('/clients-api/orders', $query);
    }

    public function order(int|string $orderId): Response
    {
        return $this->get('/clients-api/orders/' . $orderId);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createOrder(array $data): Response
    {
        return $this->post('/clients-api/orders', $data);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function portabilities(array $query = []): Response
    {
        return $this->get('/clients-api/portability', $query);
    }

    public function portability(int|string $portabilityId): Response
    {
        return $this->get('/clients-api/portability/' . $portabilityId);
    }

    public function portabilityTransitories(): Response
    {
        return $this->get('/clients-api/portability/transitories');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function requestPortability(array $data): Response
    {
        return $this->post('/clients-api/portability/request', $data);
    }

    public function deletePortability(int|string $portabilityId): Response
    {
        return $this->delete('/clients-api/portability/' . $portabilityId);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function services(array $query = []): Response
    {
        return $this->get('/clients-api/services', $query);
    }

    public function service(string $msisdn): Response
    {
        return $this->get('/clients-api/services/' . $msisdn);
    }

    public function serviceProfile(string $msisdn): Response
    {
        return $this->get('/clients-api/services/' . $msisdn . '/profile');
    }

    public function serviceBags(string $msisdn): Response
    {
        return $this->get('/clients-api/services/' . $msisdn . '/bags');
    }

    public function activateOptions(string $msisdn): Response
    {
        return $this->get('/clients-api/services/' . $msisdn . '/activate-options');
    }

    public function topupOptions(string $msisdn): Response
    {
        return $this->get('/clients-api/services/' . $msisdn . '/topup-options');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function activateService(string $msisdn, array $data = []): Response
    {
        return $this->post('/clients-api/services/' . $msisdn . '/activate', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function topupService(string $msisdn, array $data): Response
    {
        return $this->post('/clients-api/services/' . $msisdn . '/topup', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateServiceName(string $msisdn, array $data): Response
    {
        return $this->post('/clients-api/services/' . $msisdn . '/update-name', $data);
    }

    public function suspendService(string $msisdn): Response
    {
        return $this->post('/clients-api/services/' . $msisdn . '/suspend');
    }

    public function resumeService(string $msisdn): Response
    {
        return $this->post('/clients-api/services/' . $msisdn . '/resume');
    }

    /**
     * @param array<string, mixed> $query
     */
    public function users(array $query = []): Response
    {
        return $this->get('/clients-api/users', $query);
    }

    public function user(string $clientUserUri): Response
    {
        return $this->get('/clients-api/users/' . $clientUserUri);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createUser(array $data): Response
    {
        return $this->post('/clients-api/users', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateUser(string $clientUserUri, array $data): Response
    {
        return $this->put('/clients-api/users/' . $clientUserUri, $data);
    }

    public function deleteUser(string $clientUserUri): Response
    {
        return $this->delete('/clients-api/users/' . $clientUserUri);
    }
}
