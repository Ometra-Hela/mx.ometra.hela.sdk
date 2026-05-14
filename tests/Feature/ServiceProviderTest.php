<?php

namespace Ometra\HelaSdk\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Ometra\HelaSdk\Clients\AusterClient;
use Ometra\HelaSdk\Dtos\ApiResponseDto;
use Ometra\HelaSdk\Dtos\AuthTokenDto;
use Ometra\HelaSdk\Dtos\DtoCollection;
use Ometra\HelaSdk\Dtos\OfferDto;
use Ometra\HelaSdk\Dtos\OrderDto;
use Ometra\HelaSdk\Dtos\ServiceDto;
use Ometra\HelaSdk\Dtos\UserProfileDto;
use Ometra\HelaSdk\Exceptions\HelaRequestException;
use Ometra\HelaSdk\Facades\HelaSdk as HelaSdkFacade;
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
            'https://auster.example.test/api/catalogs/offers*' => Http::response([
                'data' => [
                    'current_page' => 1,
                    'data' => [
                        [
                            'offer_id' => 'HLA-10',
                            'public_name' => 'Plan 10',
                            'public_price' => 100,
                        ],
                    ],
                    'total' => 1,
                ],
            ]),
        ]);

        $this->app['config']->set('hela-sdk.source', 'heimdal');
        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test/');
        $this->app['config']->set('hela-sdk.auster.token', 'secret');

        $offers = HelaSdkFacade::auster()->offers(['status' => 'active']);

        $this->assertInstanceOf(DtoCollection::class, $offers);
        $this->assertSame(1, $offers->count());
        $this->assertSame(1, $offers->meta['current_page']);
        $this->assertInstanceOf(OfferDto::class, $offers->first());
        $this->assertSame('HLA-10', $offers->first()->id);
        $this->assertSame(100.0, $offers->first()->publicPrice);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://auster.example.test/api/catalogs/offers?status=active'
                && $request->hasHeader('Authorization', 'Bearer secret')
                && $request->hasHeader('X-Hela-App', 'heimdal');
        });
    }

    public function test_dtos_serialize_with_canonical_field_names(): void
    {
        $offer = OfferDto::from([
            'offer_id' => 'HLA-10',
            'supplementary_id' => 'SUP-10',
            'public_name' => 'Plan 10',
            'public_price' => '100.50',
            'service_type' => 'PRE',
            'validity' => 30,
            'validity_units' => 'days',
            'expiration_units' => 'months',
        ]);

        $serialized = $offer->toArray();

        $this->assertSame('HLA-10', $serialized['id']);
        $this->assertSame('SUP-10', $serialized['supplementaryId']);
        $this->assertSame('Plan 10', $serialized['publicName']);
        $this->assertSame(100.5, $serialized['publicPrice']);
        $this->assertSame(30, $serialized['validity']);
        $this->assertSame('days', $serialized['validityUnits']);
        $this->assertSame('months', $serialized['expirationUnits']);
        $this->assertSame('PRE', $serialized['serviceType']);
        $this->assertArrayNotHasKey('public_name', $offer->jsonSerialize());
        $this->assertSame('Plan 10', $offer->public_name);
        $this->assertSame('100.50', $offer->public_price);
    }

    public function test_order_dto_serializes_nested_collections(): void
    {
        $order = OrderDto::from([
            'id_order' => 501,
            'id_client' => 20,
            'order_total' => '199.99',
            'items' => [
                [
                    'id_orderItem' => 'IT-1',
                    'description' => 'Plan',
                    'final_price' => '199.99',
                ],
            ],
            'payments' => [
                [
                    'id_payment' => 'PAY-1',
                    'payment_method' => 'PAYPAL',
                    'amount' => '199.99',
                    'status' => 'APPROVED',
                ],
            ],
        ]);

        $this->assertSame(501, $order->toArray()['id']);
        $this->assertSame(199.99, $order->toArray()['total']);
        $this->assertSame('IT-1', $order->toArray()['items'][0]['id']);
        $this->assertSame('PAYPAL', $order->toArray()['payments'][0]['method']);
    }

    public function test_service_dto_preserves_dynamic_attributes_as_camel_case(): void
    {
        $service = ServiceDto::from([
            'id_service' => 10,
            'id_client' => 20,
            'dt_expiry' => '2026-06-01',
            'link_attempts' => 2,
            'offer' => [
                'public_name' => 'Plan 10',
                'service_type' => 'PRE',
            ],
            'last_topup' => [
                'dt_execution' => '2026-05-01',
            ],
        ])->toArray();

        $this->assertSame(10, $service['id']);
        $this->assertSame('2026-06-01', $service['dtExpiry']);
        $this->assertSame(2, $service['linkAttempts']);
        $this->assertSame('Plan 10', $service['offer']['publicName']);
        $this->assertSame('PRE', $service['offer']['serviceType']);
        $this->assertSame('2026-05-01', $service['lastTopup']['dtExecution']);
        $this->assertArrayNotHasKey('dt_expiry', $service);
    }

    public function test_auster_client_exposes_known_api_routes(): void
    {
        Http::fake([
            'https://auster.example.test/api/services/msisdn/525512345678' => Http::response([
                'data' => [
                    'id_service' => 10,
                    'id_client' => 20,
                    'msisdn' => '525512345678',
                    'status' => 'ACTIVE',
                ],
            ]),
            'https://auster.example.test/api/orders/100/process' => Http::response([
                'message' => 'Orden procesada',
            ]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');

        $service = HelaSdkFacade::auster()->serviceByMsisdn('525512345678');
        $process = HelaSdkFacade::auster()->processOrder(100);

        $this->assertInstanceOf(ServiceDto::class, $service);
        $this->assertSame('525512345678', $service->msisdn);
        $this->assertInstanceOf(ApiResponseDto::class, $process);
        $this->assertSame('Orden procesada', $process->message);
        Http::assertSentCount(2);
    }

    public function test_auster_clients_api_without_explicit_token_does_not_send_authorization(): void
    {
        Http::fake([
            'https://auster.example.test/clients-api/client-profile' => Http::response([
                'data' => ['id_client' => 20, 'email' => 'client@example.test'],
            ]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');

        $profile = HelaSdkFacade::auster()->clientsApi()->clientProfile();

        $this->assertInstanceOf(UserProfileDto::class, $profile);
        $this->assertSame(20, $profile->clientId);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://auster.example.test/clients-api/client-profile'
                && ! $request->hasHeader('Authorization');
        });
    }

    public function test_auster_clients_api_can_use_user_session_tokens(): void
    {
        Http::fake([
            'https://auster.example.test/clients-api/user-profile' => Http::response([
                'data' => [
                    'uri_clientUser' => 'user-1',
                    'id_client' => 20,
                    'email' => 'user@example.test',
                    'name' => 'Test User',
                ],
            ]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');

        $profile = HelaSdkFacade::auster()->clientsApiAsUser('user-token')->userProfile();

        $this->assertInstanceOf(UserProfileDto::class, $profile);
        $this->assertSame('user@example.test', $profile->email);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://auster.example.test/clients-api/user-profile'
                && $request->hasHeader('Authorization', 'Bearer USR-user-token');
        });
    }

    public function test_auster_clients_api_login_does_not_require_configured_token(): void
    {
        Http::fake([
            'https://auster.example.test/clients-api/authentication/login' => Http::response([
                'data' => [
                    'token' => 'session-token',
                    'uri_clientUser' => 'user-1',
                ],
            ]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');

        $login = HelaSdkFacade::auster()->clientsApi()->login([
            'email' => 'client@example.test',
            'password' => 'secret',
        ]);

        $this->assertInstanceOf(AuthTokenDto::class, $login);
        $this->assertSame('session-token', $login->token);
        $this->assertSame('user-1', $login->clientUserUri);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://auster.example.test/clients-api/authentication/login'
                && ! $request->hasHeader('Authorization');
        });
    }

    public function test_auster_clients_api_as_client_always_uses_api_prefix(): void
    {
        Http::fake([
            'https://auster.example.test/clients-api/client-profile' => Http::response([
                'data' => ['id_client' => 20, 'email' => 'client@example.test'],
            ]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');

        $profile = HelaSdkFacade::auster()->clientsApiAsClient('client-token')->clientProfile();

        $this->assertInstanceOf(UserProfileDto::class, $profile);
        $this->assertSame('client@example.test', $profile->email);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://auster.example.test/clients-api/client-profile'
                && $request->hasHeader('Authorization', 'Bearer API-client-token');
            });
    }

    public function test_auster_clients_api_exposes_heartbeat_route(): void
    {
        Http::fake([
            'https://auster.example.test/clients-api/monitoring/heartbeat' => Http::response([
                'message' => 'Heartbeat received',
            ]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');

        $response = HelaSdkFacade::auster()->clientsApiAsClient('client-token')->heartbeat([
            'status' => 1,
            'memory_usage' => 123456,
        ]);

        $this->assertSame('Heartbeat received', $response->message);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://auster.example.test/clients-api/monitoring/heartbeat'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer API-client-token')
                && $request['status'] === 1
                && $request['memory_usage'] === 123456;
        });
    }

    public function test_auster_clients_api_exposes_service_change_routes(): void
    {
        Http::fake([
            'https://auster.example.test/clients-api/services/525512345678/replacement-options' => Http::response([
                'data' => [['offer_id' => 'REP-1', 'public_name' => 'Replacement']],
            ]),
            'https://auster.example.test/clients-api/services/525512345678/renew-options' => Http::response([
                'data' => [['offer_id' => 'REN-1', 'public_name' => 'Renewal']],
            ]),
            'https://auster.example.test/clients-api/services/525512345678/renew' => Http::response([
                'data' => ['id_order' => 501, 'total' => 199],
            ]),
            'https://auster.example.test/clients-api/services/525512345678/replace-offer' => Http::response([
                'message' => 'Offer replaced',
            ]),
            'https://auster.example.test/clients-api/services/525512345678/replace-sim-card' => Http::response([
                'message' => 'SIM card replaced',
            ]),
            'https://auster.example.test/clients-api/imei/123456789012345/lock' => Http::response([
                'message' => 'IMEI locked',
            ]),
            'https://auster.example.test/clients-api/imei/123456789012345/unlock' => Http::response([
                'message' => 'IMEI unlocked',
            ]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');
        $client = HelaSdkFacade::auster()->clientsApiAsClient('client-token');

        $replacementOptions = $client->replacementOptions('525512345678');
        $renewOptions = $client->renewOptions('525512345678');
        $renewOrder = $client->renewService('525512345678', ['offer_id' => 'REN-1']);
        $replaceOffer = $client->replaceOffer('525512345678', ['offer_id' => 'REP-1']);
        $replaceSimCard = $client->replaceSimCard('525512345678', ['simcard' => '8952020000000000000']);
        $imeiLock = $client->imeiLock('123456789012345');
        $imeiUnlock = $client->imeiUnlock('123456789012345');

        $this->assertInstanceOf(DtoCollection::class, $replacementOptions);
        $this->assertInstanceOf(OfferDto::class, $replacementOptions->first());
        $this->assertInstanceOf(DtoCollection::class, $renewOptions);
        $this->assertInstanceOf(OrderDto::class, $renewOrder);
        $this->assertSame(501, $renewOrder->id);
        $this->assertSame('Offer replaced', $replaceOffer->message);
        $this->assertSame('SIM card replaced', $replaceSimCard->message);
        $this->assertSame('IMEI locked', $imeiLock->message);
        $this->assertSame('IMEI unlocked', $imeiUnlock->message);
        Http::assertSentCount(7);
    }

    public function test_failed_responses_throw_structured_exception(): void
    {
        Http::fake([
            'https://auster.example.test/api/catalogs/offers*' => Http::response([
                'message' => 'Invalid request',
                'errors' => ['status' => ['Invalid status']],
            ], 422),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');

        try {
            HelaSdkFacade::auster()->offers(['status' => 'invalid']);
            $this->fail('Expected a HELA request exception.');
        } catch (HelaRequestException $exception) {
            $this->assertSame(422, $exception->status);
            $this->assertSame('Invalid request', $exception->getMessage());
            $this->assertSame(['status' => ['Invalid status']], $exception->errors);
        }
    }
}
