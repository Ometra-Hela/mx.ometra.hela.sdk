<?php

namespace Ometra\HelaSdk\Clients;

use Illuminate\Http\Client\Response;

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
     */
    public function offers(array $query = []): Response
    {
        return $this->get('/api/catalogs/offers', $query);
    }

    public function offer(int|string $offerId): Response
    {
        return $this->get('/api/catalogs/offers/' . $offerId);
    }

    public function portabilitiesByMsisdn(string $msisdn): Response
    {
        return $this->get('/api/catalogs/portability/msisdn/' . $msisdn);
    }

    public function serviceByMsisdn(string $msisdn): Response
    {
        return $this->get('/api/services/msisdn/' . $msisdn);
    }

    public function serviceSupplementaries(string $msisdn): Response
    {
        return $this->get('/api/services/msisdn/' . $msisdn . '/supplementaries');
    }

    public function serviceReplacements(string $msisdn): Response
    {
        return $this->get('/api/services/msisdn/' . $msisdn . '/replacements');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validateActivationKey(array $data): Response
    {
        return $this->post('/api/services/activations/validate/activation-key', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validateSimCard(array $data): Response
    {
        return $this->post('/api/services/activations/validate/sim-card', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function activateService(array $data): Response
    {
        return $this->post('/api/services/activations/activate', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createOrder(array $data): Response
    {
        return $this->post('/api/orders/new', $data);
    }

    public function order(int|string $orderId): Response
    {
        return $this->get('/api/orders/' . $orderId);
    }

    public function orderByMsisdn(string $msisdn): Response
    {
        return $this->get('/api/orders/msisdn/' . $msisdn);
    }

    public function orderPayment(int|string $orderId): Response
    {
        return $this->get('/api/orders/' . $orderId . '/payment');
    }

    public function publishOrder(int|string $orderId): Response
    {
        return $this->post('/api/orders/' . $orderId . '/publish');
    }

    public function processOrder(int|string $orderId): Response
    {
        return $this->post('/api/orders/' . $orderId . '/process');
    }

    public function cancelOrder(int|string $orderId): Response
    {
        return $this->post('/api/orders/' . $orderId . '/cancel');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function addOrderPayment(int|string $orderId, array $data): Response
    {
        return $this->post('/api/orders/' . $orderId . '/add-payment', $data);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function shippingQuotes(array $query = []): Response
    {
        return $this->get('/api/shipping/quotes', $query);
    }

    public function validatePayment(int|string $paymentId): Response
    {
        return $this->post('/api/payments/' . $paymentId . '/validate');
    }

    public function cancelPayment(int|string $paymentId): Response
    {
        return $this->post('/api/payments/' . $paymentId . '/cancel');
    }
}
