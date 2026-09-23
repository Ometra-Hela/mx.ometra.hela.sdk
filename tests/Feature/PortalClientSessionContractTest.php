<?php

namespace Ometra\HelaSdk\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Ometra\HelaSdk\Dtos\FederatedSessionDto;
use Ometra\HelaSdk\Facades\HelaSdk;
use Ometra\HelaSdk\Tests\TestCase;

final class PortalClientSessionContractTest extends TestCase
{
    public function test_client_exchange_sends_scoped_token_and_authentication_transaction(): void
    {
        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');
        Http::fake(['*/portal/exchange' => Http::response(['data' => [
            'access_token' => 'user-token', 'refresh_token' => 'refresh-token',
            'access_token_expires_at' => '2026-09-23T13:15:00Z',
            'absolute_expires_at' => '2026-09-23T21:00:00Z',
            'user' => ['id' => 'user-a', 'client_id' => 'client-a', 'email' => 'user@example.test'],
        ]])]);

        $client = HelaSdk::auster()->clientsApiAsClient('client-token');
        $session = $client->exchangePortalClientSession('user-a', ['pwd', 'otp'], 'transaction-1');

        $this->assertInstanceOf(FederatedSessionDto::class, $session);
        $this->assertSame('user-token', $session->accessToken);
        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && $request->url() === 'https://auster.example.test/clients-api/authentication/portal/exchange'
            && $request->hasHeader('Authorization', 'Bearer API-client-token')
            && $request->data() === [
                'user_id' => 'user-a', 'methods' => ['pwd', 'otp'], 'authentication_id' => 'transaction-1',
            ]);
    }
}
