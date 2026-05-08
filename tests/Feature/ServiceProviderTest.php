<?php

namespace Ometra\HelaSdk\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Ometra\HelaSdk\Facades\HelaSdk as HelaSdkFacade;
use Ometra\HelaSdk\Clients\AusterClient;
use Ometra\HelaSdk\HelaSdk;
use Ometra\HelaSdk\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_it_registers_the_sdk_singleton(): void
    {
        $this->assertInstanceOf(HelaSdk::class, $this->app->make(HelaSdk::class));
        $this->assertSame($this->app->make(HelaSdk::class), $this->app->make('hela-sdk'));
    }

    public function test_it_merges_package_configuration(): void
    {
        $this->assertSame(30, config('hela-sdk.timeout'));
        $this->assertSame(['times' => 0, 'sleep' => 100], config('hela-sdk.retry'));
    }

    public function test_facade_resolves_the_sdk(): void
    {
        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test/');
        $this->app['config']->set('hela-sdk.auster.token', 'secret');

        $this->assertSame('https://auster.example.test', HelaSdkFacade::baseUrl());
        $this->assertSame('secret', HelaSdkFacade::apiKey());
        $this->assertInstanceOf(AusterClient::class, HelaSdkFacade::auster());
    }

    public function test_auster_client_sends_bearer_token_and_source_header(): void
    {
        Http::fake([
            'https://auster.example.test/api/catalogs/offers*' => Http::response(['ok' => true]),
        ]);

        $this->app['config']->set('hela-sdk.source', 'heimdal');
        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test/');
        $this->app['config']->set('hela-sdk.auster.token', 'secret');

        HelaSdkFacade::auster()->offers(['status' => 'active']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://auster.example.test/api/catalogs/offers?status=active'
                && $request->hasHeader('Authorization', 'Bearer secret')
                && $request->hasHeader('X-Hela-App', 'heimdal');
        });
    }

    public function test_auster_client_exposes_known_api_routes(): void
    {
        Http::fake([
            'https://auster.example.test/api/services/msisdn/525512345678' => Http::response(['ok' => true]),
            'https://auster.example.test/api/orders/100/process' => Http::response(['ok' => true]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');

        HelaSdkFacade::auster()->serviceByMsisdn('525512345678');
        HelaSdkFacade::auster()->processOrder(100);

        Http::assertSentCount(2);
    }

    public function test_auster_clients_api_uses_prefixed_client_api_token(): void
    {
        Http::fake([
            'https://auster.example.test/clients-api/client-profile' => Http::response(['ok' => true]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');
        $this->app['config']->set('hela-sdk.auster.clients_api.token', 'client-token');

        HelaSdkFacade::auster()->clientsApi()->clientProfile();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://auster.example.test/clients-api/client-profile'
                && $request->hasHeader('Authorization', 'Bearer API-client-token');
        });
    }

    public function test_auster_clients_api_can_use_user_session_tokens(): void
    {
        Http::fake([
            'https://auster.example.test/clients-api/user-profile' => Http::response(['ok' => true]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');

        HelaSdkFacade::auster()->clientsApiAsUser('user-token')->userProfile();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://auster.example.test/clients-api/user-profile'
                && $request->hasHeader('Authorization', 'Bearer USR-user-token');
        });
    }

    public function test_auster_clients_api_login_does_not_require_configured_token(): void
    {
        Http::fake([
            'https://auster.example.test/clients-api/authentication/login' => Http::response(['ok' => true]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');
        $this->app['config']->set('hela-sdk.auster.clients_api.token', 'client-api-token');

        HelaSdkFacade::auster()->clientsApi()->login([
            'email' => 'client@example.test',
            'password' => 'secret',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://auster.example.test/clients-api/authentication/login'
                && ! $request->hasHeader('Authorization');
        });
    }

    public function test_auster_clients_api_as_client_always_uses_api_prefix(): void
    {
        Http::fake([
            'https://auster.example.test/clients-api/client-profile' => Http::response(['ok' => true]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');

        HelaSdkFacade::auster()->clientsApiAsClient('client-token')->clientProfile();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://auster.example.test/clients-api/client-profile'
                && $request->hasHeader('Authorization', 'Bearer API-client-token');
        });
    }

    public function test_auster_clients_api_configured_token_is_always_a_client_api_token(): void
    {
        Http::fake([
            'https://auster.example.test/clients-api/client-profile' => Http::response(['ok' => true]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');
        $this->app['config']->set('hela-sdk.auster.clients_api.token', 'USR-client-token');

        HelaSdkFacade::auster()->clientsApi()->clientProfile();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://auster.example.test/clients-api/client-profile'
                && $request->hasHeader('Authorization', 'Bearer API-client-token');
        });
    }
}
