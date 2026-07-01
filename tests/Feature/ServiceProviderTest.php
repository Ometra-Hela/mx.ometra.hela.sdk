<?php

namespace Ometra\HelaSdk\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Ometra\HelaSdk\Clients\AlizeClient;
use Ometra\HelaSdk\Clients\AusterClient;
use Ometra\HelaSdk\Dtos\ApiResponseDto;
use Ometra\HelaSdk\Dtos\AuthTokenDto;
use Ometra\HelaSdk\Dtos\DtoCollection;
use Ometra\HelaSdk\Dtos\GenericDto;
use Ometra\HelaSdk\Dtos\OfferDto;
use Ometra\HelaSdk\Dtos\OrderDto;
use Ometra\HelaSdk\Dtos\OrderItemDto;
use Ometra\HelaSdk\Dtos\ServiceBulkOperationDto;
use Ometra\HelaSdk\Dtos\ServiceDto;
use Ometra\HelaSdk\Dtos\ServiceGroupDto;
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
        $this->assertSame(1000, config('hela-sdk.slow_log_ms'));
    }

    public function test_it_registers_the_alize_client(): void
    {
        $this->app['config']->set('hela-sdk.alize.base_url', 'https://alize.example.test/');
        $this->app['config']->set('hela-sdk.alize.token', 'alize-secret');

        $this->assertInstanceOf(AlizeClient::class, HelaSdkFacade::alize());
        $this->assertSame('https://alize.example.test', HelaSdkFacade::alize()->baseUrl());
        $this->assertSame('alize-secret', HelaSdkFacade::alize()->token());
    }

    public function test_alize_client_exposes_portability_routes(): void
    {
        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            $method = $request->method();

            return match ([$method, $path]) {
                ['GET', '/api/portabilities'] => Http::response([
                    'data' => [['id' => 10, 'state' => 'PORT_SCHEDULED']],
                ]),
                ['GET', '/api/portabilities/10'] => Http::response([
                    'data' => ['id' => 10, 'state' => 'PORT_SCHEDULED'],
                ]),
                ['GET', '/api/portabilities/msisdn/525512345678'] => Http::response([
                    'data' => ['id' => 10, 'state' => 'PORT_SCHEDULED'],
                ]),
                ['GET', '/api/portabilities/transitories'] => Http::response([
                    'data' => [['msisdn' => '525500000001']],
                ]),
                ['POST', '/api/portabilities/validate'] => Http::response([
                    'valid' => true,
                    'data' => ['external_client_id' => 'CLI-1'],
                ]),
                ['POST', '/api/portabilities'] => Http::response([
                    'data' => ['id' => 10, 'state' => 'REQUESTED'],
                ], 201),
                ['DELETE', '/api/portabilities/10'] => Http::response([
                    'message' => 'Portability deleted',
                ]),
                ['POST', '/api/alize/portabilities/10/execute'] => Http::response([
                    'message' => 'Portability execute accepted.',
                ]),
                ['POST', '/api/alize/portabilities/10/complete'] => Http::response([
                    'message' => 'Portability completion applied.',
                ]),
                default => Http::response(['message' => 'Unexpected request'], 404),
            };
        });

        $this->app['config']->set('hela-sdk.source', 'auster');
        $this->app['config']->set('hela-sdk.alize.base_url', 'https://alize.example.test');
        $this->app['config']->set('hela-sdk.alize.token', 'alize-secret');

        $client = HelaSdkFacade::alize();
        $portabilities = $client->portabilities(['status' => 'pending']);
        $portability = $client->portability(10);
        $byMsisdn = $client->portabilitiesByMsisdn('525512345678');
        $transitories = $client->portabilityTransitories();
        $created = $client->requestPortability([
            'numbers' => [[
                'msisdn_ported' => '525512345678',
                'msisdn_transitory' => '525500000001',
                'nip' => '1234',
            ]],
        ]);
        $validated = $client->validatePortability([
            'numbers' => [[
                'msisdn_ported' => '525512345678',
                'msisdn_transitory' => '525500000001',
                'nip' => '1234',
            ]],
        ]);
        $deleted = $client->deletePortability(10);
        $execute = $client->executeAusterPortability(10, ['idempotency_key' => 'key']);
        $complete = $client->completeAusterPortability(10, ['idempotency_key' => 'key']);

        $this->assertInstanceOf(DtoCollection::class, $portabilities);
        $this->assertSame('PORT_SCHEDULED', $portabilities->first()->state);
        $this->assertSame(10, $portability->id);
        $this->assertSame(10, $byMsisdn->id);
        $this->assertSame('525500000001', $transitories->first()->msisdn);
        $this->assertSame('REQUESTED', $created->state);
        $this->assertSame('CLI-1', $validated->external_client_id);
        $this->assertSame('Portability deleted', $deleted->message);
        $this->assertSame('Portability execute accepted.', $execute->message);
        $this->assertSame('Portability completion applied.', $complete->message);

        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return parse_url($request->url(), PHP_URL_PATH) === '/api/portabilities'
                && ($query['status'] ?? null) === 'pending'
                && $request->hasHeader('Authorization', 'Bearer alize-secret')
                && $request->hasHeader('X-Hela-App', 'auster');
        });
        Http::assertSentCount(9);
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

    public function test_offer_dto_exposes_auster_offer_flags_and_commissions(): void
    {
        $offer = OfferDto::from([
            'offer_id' => 'HLA-20',
            'public_name' => 'Plan 20',
            'allows_new_line_activation' => 1,
            'commission_enabled' => 'true',
            'commission_activation_amount' => '35.50',
            'commission_renewal_amount' => 20,
            'commission_portability_rate' => '0.15',
            'commission_retention_rate' => '0.05',
            'commission_retention_months' => '6',
            'commission_retention_enabled' => 0,
            'commission_notes' => 'Pago mensual',
            'supplementaries' => [
                ['offer_id' => 'SUP-1', 'public_name' => 'Extra data'],
            ],
            'replacements' => [
                ['offer_id' => 'REP-1', 'public_name' => 'Replacement'],
            ],
        ]);

        $serialized = $offer->toArray();

        $this->assertTrue($offer->allowsNewLineActivation);
        $this->assertTrue($offer->commissionEnabled);
        $this->assertSame(35.5, $offer->commissionActivationAmount);
        $this->assertSame(20.0, $offer->commissionRenewalAmount);
        $this->assertSame(0.15, $offer->commissionPortabilityRate);
        $this->assertSame(0.05, $offer->commissionRetentionRate);
        $this->assertSame(6, $offer->commissionRetentionMonths);
        $this->assertFalse($offer->commissionRetentionEnabled);
        $this->assertSame('Pago mensual', $offer->commissionNotes);
        $this->assertSame('SUP-1', $offer->supplementaries[0]['offer_id']);
        $this->assertSame('REP-1', $offer->replacements[0]['offer_id']);
        $this->assertTrue($serialized['allowsNewLineActivation']);
        $this->assertSame('Extra data', $serialized['supplementaries'][0]['publicName']);
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
        $this->assertNull($service['groupId']);
        $this->assertSame('2026-06-01', $service['dtExpiry']);
        $this->assertSame(2, $service['linkAttempts']);
        $this->assertSame('Plan 10', $service['offer']['publicName']);
        $this->assertSame('PRE', $service['offer']['serviceType']);
        $this->assertSame('2026-05-01', $service['lastTopup']['dtExecution']);
        $this->assertArrayNotHasKey('dt_expiry', $service);
    }

    public function test_service_dto_exposes_group_fields_and_preserves_attributes(): void
    {
        $service = ServiceDto::from([
            'id_service' => 10,
            'id_serviceGroup' => 7,
            'group_name' => 'Operaciones',
            'group_icon' => 'briefcase',
        ]);

        $this->assertSame(7, $service->groupId);
        $this->assertSame('Operaciones', $service->groupName);
        $this->assertSame('briefcase', $service->groupIcon);
        $this->assertSame(7, $service->id_serviceGroup);
        $this->assertSame('Operaciones', $service->group_name);
    }

    public function test_service_dto_exposes_auster_service_display_linking_and_consumption_fields(): void
    {
        $service = ServiceDto::from([
            'id_service' => 10,
            'id_client' => 'CLI-1',
            'client_name' => 'Acme',
            'name' => 'Linea comercial',
            'msisdn' => '525512345678',
            'service_type' => 'Prepago',
            'service_type_code' => 'PRE',
            'status' => 'ACTIVE',
            'status_label' => 'Activo',
            'status_variant' => 'success',
            'altan_status' => 'ACTIVE',
            'is_linked' => true,
            'requires_linking' => false,
            'linking' => [
                'status' => 'linked',
                'attempts' => 1,
            ],
            'offer_name' => 'Plan 20',
            'product' => 'MBB',
            'registration_date' => '01-06-2026',
            'last_topup_date' => '2026-06-01',
            'last_topup_expiry' => '2026-06-30',
            'expiry_date' => '30-06-2026',
            'dt_expiry' => '2026-06-30',
            'expiry_days' => '25',
            'is_near_expiry' => 1,
            'consumption_summary' => [
                'items' => [
                    ['offering_id' => 'DATA-1', 'offer_name' => 'Bolsa datos'],
                ],
            ],
        ]);

        $serialized = $service->toArray();

        $this->assertSame('Acme', $service->clientName);
        $this->assertSame('Linea comercial', $service->name);
        $this->assertSame('Prepago', $service->serviceType);
        $this->assertSame('PRE', $service->serviceTypeCode);
        $this->assertSame('Activo', $service->statusLabel);
        $this->assertSame('success', $service->statusVariant);
        $this->assertTrue($service->isLinked);
        $this->assertFalse($service->requiresLinking);
        $this->assertSame('linked', $service->linking['status']);
        $this->assertSame('Plan 20', $service->offerName);
        $this->assertSame('MBB', $service->product);
        $this->assertSame('01-06-2026', $service->registrationDate);
        $this->assertSame('2026-06-01', $service->lastTopupDate);
        $this->assertSame('2026-06-30', $service->lastTopupExpiry);
        $this->assertSame('30-06-2026', $service->expiryDate);
        $this->assertSame('2026-06-30', $service->dtExpiry);
        $this->assertSame(25, $service->expiryDays);
        $this->assertTrue($service->isNearExpiry);
        $this->assertSame('Bolsa datos', $service->consumptionSummary['items'][0]['offerName']);
        $this->assertSame('Bolsa datos', $serialized['consumptionSummary']['items'][0]['offerName']);
        $this->assertArrayNotHasKey('status_label', $serialized);
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
            'https://auster.example.test/api/zephyr-runner/instances' => Http::response([
                'data' => [['id_instance' => 1, 'id_client' => 'CLI-1', 'desired_status' => 'active']],
            ]),
            'https://auster.example.test/api/zephyr-runner/instances/status' => Http::response([
                'message' => 'Instance status updated.',
                'data' => ['updated' => 1],
            ]),
            'https://auster.example.test/api/orders/100/process' => Http::response([
                'message' => 'Orden procesada',
            ]),
            'https://auster.example.test/api/orders/100/unpublish' => Http::response([
                'data' => ['id_order' => 100, 'published' => false],
            ]),
            'https://auster.example.test/api/orders/100/set-discount-code' => Http::response([
                'message' => 'Discount code applied.',
            ]),
            'https://auster.example.test/api/orders/100/items' => Http::response([
                'data' => ['item' => ['id_orderItem' => 'ITM-1', 'item_type' => 'sim', 'description' => 'SIM']],
            ], 201),
            'https://auster.example.test/api/orders/100/items/bulk-create' => Http::response([
                'data' => ['created' => 2],
            ], 201),
            'https://auster.example.test/api/orders/100/items/bulk-assign-targets' => Http::response([
                'data' => ['assigned' => 2],
            ]),
            'https://auster.example.test/api/orders/100/items/ITM-1' => Http::response([
                'data' => ['item' => ['id_orderItem' => 'ITM-1', 'target' => '525512345678']],
            ]),
            'https://auster.example.test/api/services/gift-cards/validate' => Http::response([
                'data' => ['valid' => true, 'amount' => 100],
            ]),
            'https://auster.example.test/api/services/gift-cards/redeem' => Http::response([
                'data' => ['redeemed' => true],
            ]),
            'https://auster.example.test/api/clients/verification' => Http::response([
                'data' => ['verified' => true],
            ]),
            'https://auster.example.test/api/clients/generate-code' => Http::response([
                'message' => 'Verification code generated.',
            ]),
            'https://auster.example.test/api/clients/confirm-code' => Http::response([
                'message' => 'Verification code confirmed.',
            ]),
            'https://auster.example.test/api/clients/generate-ticket' => Http::response([
                'message' => 'Verification ticket generated.',
            ]),
            'https://auster.example.test/api/clients?filter=Acme' => Http::response([
                'data' => [
                    ['id_client' => 'CLI-1', 'name' => 'Acme', 'email' => 'ops@example.test'],
                ],
            ]),
            'https://auster.example.test/api/clients/CLI-1/services?status=ACTIVE' => Http::response([
                'data' => [
                    ['id_service' => 20, 'id_client' => 'CLI-1', 'msisdn' => '525500000001', 'status' => 'ACTIVE'],
                ],
            ]),
            'https://auster.example.test/api/alize/portabilities/10/execute' => Http::response([
                'message' => 'Portability execute accepted.',
            ]),
            'https://auster.example.test/api/alize/portabilities/10/complete' => Http::response([
                'message' => 'Portability completion applied.',
            ]),
            'https://auster.example.test/api/support/services/525512345678/suspend-stolen' => Http::response([
                'message' => 'Service suspended for theft.',
                'data' => ['reason' => 'stolen'],
            ]),
            'https://auster.example.test/api/support/imei/359881234567890/lock' => Http::response([
                'message' => 'IMEI locked by support.',
                'data' => ['imei' => '359881234567890'],
            ]),
            'https://auster.example.test/api/discounts/validate/PROMO' => Http::response([
                'data' => ['valid' => true, 'code' => 'PROMO'],
            ]),
            'https://auster.example.test/api/tools/imei/359881234567890/compatibility' => Http::response([
                'message' => 'Compatibilidad obtenida correctamente',
                'data' => ['compatibility' => true],
            ]),
            'https://auster.example.test/api/vinculacion/persona-fisica/validate' => Http::response([
                'message' => 'Persona fisica validada.',
            ]),
            'https://auster.example.test/api/vinculacion/persona-fisica/desvincula' => Http::response([
                'message' => 'Persona fisica desvinculada.',
            ]),
            'https://auster.example.test/api/vinculacion/persona-fisica/test' => Http::response([
                'message' => 'Persona fisica test ok.',
            ]),
            'https://auster.example.test/api/vinculacion/persona-moral/validate' => Http::response([
                'message' => 'Persona moral validada.',
            ]),
            'https://auster.example.test/api/vinculacion/persona-moral/register' => Http::response([
                'message' => 'Persona moral registrada.',
            ]),
            'https://auster.example.test/api/vinculacion/persona-moral/vincula' => Http::response([
                'message' => 'Persona moral vinculada.',
            ]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');

        $service = HelaSdkFacade::auster()->serviceByMsisdn('525512345678');
        $instances = HelaSdkFacade::auster()->zephyrRunnerInstances();
        $instanceStatus = HelaSdkFacade::auster()->updateZephyrRunnerInstanceStatus(['id_client' => 'CLI-1']);
        $process = HelaSdkFacade::auster()->processOrder(100);
        $unpublish = HelaSdkFacade::auster()->unpublishOrder(100);
        $discount = HelaSdkFacade::auster()->setOrderDiscountCode(100, ['code' => 'PROMO']);
        $item = HelaSdkFacade::auster()->addOrderItem(100, ['type' => 'sim']);
        $bulkCreate = HelaSdkFacade::auster()->bulkCreateOrderItems(100, ['items' => []]);
        $bulkAssign = HelaSdkFacade::auster()->bulkAssignOrderItemTargets(100, ['targets' => []]);
        $updatedItem = HelaSdkFacade::auster()->updateOrderItem(100, 'ITM-1', ['target' => '525512345678']);
        $removedItem = HelaSdkFacade::auster()->removeOrderItem(100, 'ITM-1');
        $validGiftCard = HelaSdkFacade::auster()->validateGiftCard(['code' => 'GIFT-1']);
        $redeemedGiftCard = HelaSdkFacade::auster()->redeemGiftCard(['code' => 'GIFT-1']);
        $verification = HelaSdkFacade::auster()->clientVerification();
        $generatedCode = HelaSdkFacade::auster()->generateClientVerificationCode(['email' => 'ops@example.test']);
        $confirmedCode = HelaSdkFacade::auster()->confirmClientVerificationCode(['code' => '123456']);
        $generatedTicket = HelaSdkFacade::auster()->generateClientVerificationTicket(['email' => 'ops@example.test']);
        $clients = HelaSdkFacade::auster()->clients(['filter' => 'Acme']);
        $services = HelaSdkFacade::auster()->clientServices('CLI-1', ['status' => 'ACTIVE']);
        $execute = HelaSdkFacade::auster()->executeAlizePortability(10, ['idempotency_key' => 'key']);
        $complete = HelaSdkFacade::auster()->completeAlizePortability(10, ['idempotency_key' => 'key']);
        $suspendStolen = HelaSdkFacade::auster()->supportSuspendServiceForTheft('525512345678', [
            'incident_folio' => 'STR-20260523-0001',
        ]);
        $lockImei = HelaSdkFacade::auster()->supportLockImei('359881234567890', [
            'incident_folio' => 'STR-20260523-0001',
        ]);
        $discountCode = HelaSdkFacade::auster()->validateDiscountCode('PROMO');
        $imeiCompatibility = HelaSdkFacade::auster()->imeiCompatibility('359881234567890');
        $personaFisica = HelaSdkFacade::auster()->validatePersonaFisica(['msisdn' => '525512345678']);
        $desvinculaPersonaFisica = HelaSdkFacade::auster()->desvinculaPersonaFisica(['msisdn' => '525512345678']);
        $testPersonaFisica = HelaSdkFacade::auster()->testPersonaFisica(['msisdn' => '525512345678']);
        $personaMoral = HelaSdkFacade::auster()->validatePersonaMoral(['rfc' => 'XAXX010101000']);
        $registerPersonaMoral = HelaSdkFacade::auster()->registerPersonaMoral(['rfc' => 'XAXX010101000']);
        $vinculaPersonaMoral = HelaSdkFacade::auster()->vinculaPersonaMoral(['rfc' => 'XAXX010101000']);

        $this->assertInstanceOf(ServiceDto::class, $service);
        $this->assertSame('525512345678', $service->msisdn);
        $this->assertSame('CLI-1', $instances->first()->id_client);
        $this->assertSame('Instance status updated.', $instanceStatus->message);
        $this->assertInstanceOf(ApiResponseDto::class, $process);
        $this->assertSame('Orden procesada', $process->message);
        $this->assertInstanceOf(OrderDto::class, $unpublish);
        $this->assertFalse($unpublish->published);
        $this->assertSame('Discount code applied.', $discount->message);
        $this->assertInstanceOf(OrderItemDto::class, $item);
        $this->assertSame('ITM-1', $item->id);
        $this->assertSame(2, $bulkCreate->created);
        $this->assertSame(2, $bulkAssign->assigned);
        $this->assertSame('525512345678', $updatedItem->target);
        $this->assertSame(200, $removedItem->status);
        $this->assertTrue($validGiftCard->valid);
        $this->assertTrue($redeemedGiftCard->redeemed);
        $this->assertTrue($verification->verified);
        $this->assertSame('Verification code generated.', $generatedCode->message);
        $this->assertSame('Verification code confirmed.', $confirmedCode->message);
        $this->assertSame('Verification ticket generated.', $generatedTicket->message);
        $this->assertInstanceOf(DtoCollection::class, $clients);
        $this->assertInstanceOf(GenericDto::class, $clients->first());
        $this->assertSame('CLI-1', $clients->first()->id_client);
        $this->assertInstanceOf(DtoCollection::class, $services);
        $this->assertSame('525500000001', $services->first()->msisdn);
        $this->assertSame('Portability execute accepted.', $execute->message);
        $this->assertSame('Portability completion applied.', $complete->message);
        $this->assertSame('Service suspended for theft.', $suspendStolen->message);
        $this->assertSame('IMEI locked by support.', $lockImei->message);
        $this->assertTrue($discountCode->valid);
        $this->assertTrue($imeiCompatibility->compatibility);
        $this->assertSame('Persona fisica validada.', $personaFisica->message);
        $this->assertSame('Persona fisica desvinculada.', $desvinculaPersonaFisica->message);
        $this->assertSame('Persona fisica test ok.', $testPersonaFisica->message);
        $this->assertSame('Persona moral validada.', $personaMoral->message);
        $this->assertSame('Persona moral registrada.', $registerPersonaMoral->message);
        $this->assertSame('Persona moral vinculada.', $vinculaPersonaMoral->message);
        Http::assertSentCount(31);
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
            'https://auster.example.test/clients-api/authentication/social-login' => Http::response([
                'data' => [
                    'token' => 'social-session-token',
                    'uri_clientUser' => 'user-2',
                ],
            ]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');

        $login = HelaSdkFacade::auster()->clientsApi()->login([
            'email' => 'client@example.test',
            'password' => 'secret',
        ]);
        $socialLogin = HelaSdkFacade::auster()->clientsApi()->socialLogin([
            'provider' => 'google',
            'token' => 'social-token',
        ]);

        $this->assertInstanceOf(AuthTokenDto::class, $login);
        $this->assertSame('session-token', $login->token);
        $this->assertSame('user-1', $login->clientUserUri);
        $this->assertInstanceOf(AuthTokenDto::class, $socialLogin);
        $this->assertSame('social-session-token', $socialLogin->token);
        $this->assertSame('user-2', $socialLogin->clientUserUri);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://auster.example.test/clients-api/authentication/login'
                && ! $request->hasHeader('Authorization');
        });
        Http::assertSent(function ($request) {
            return $request->url() === 'https://auster.example.test/clients-api/authentication/social-login'
                && ! $request->hasHeader('Authorization');
        });
        Http::assertSentCount(2);
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

    public function test_auster_clients_api_sends_query_params_and_preserves_pagination_meta(): void
    {
        Http::fake([
            'https://auster.example.test/clients-api/orders*' => Http::response([
                'message' => 'Ordenes obtenidas correctamente',
                'data' => [
                    'current_page' => 2,
                    'data' => [
                        [
                            'id_order' => 501,
                            'order_total' => 199.99,
                        ],
                    ],
                    'from' => 16,
                    'last_page' => 4,
                    'per_page' => 15,
                    'to' => 30,
                    'total' => 58,
                ],
            ]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');

        $orders = HelaSdkFacade::auster()->clientsApiAsClient('client-token')->orders([
            'paginate' => true,
            'page' => 2,
            'per_page' => 15,
            'filter' => '5551234567',
        ]);

        $this->assertInstanceOf(DtoCollection::class, $orders);
        $this->assertSame(1, $orders->count());
        $this->assertSame(2, $orders->meta['current_page']);
        $this->assertSame(15, $orders->meta['per_page']);
        $this->assertSame(58, $orders->meta['total']);
        $this->assertInstanceOf(OrderDto::class, $orders->first());
        $this->assertSame(501, $orders->first()->id);

        Http::assertSent(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return str_starts_with($request->url(), 'https://auster.example.test/clients-api/orders?')
                && $request->hasHeader('Authorization', 'Bearer API-client-token')
                && (string) ($query['paginate'] ?? '') === '1'
                && (string) ($query['page'] ?? '') === '2'
                && (string) ($query['per_page'] ?? '') === '15'
                && ($query['filter'] ?? null) === '5551234567'
                && ! array_key_exists('search', $query);
        });
    }

    public function test_auster_client_list_methods_accept_named_query_parameters(): void
    {
        Http::fake([
            'https://auster.example.test/api/catalogs/offers*' => Http::response(['data' => []]),
            'https://auster.example.test/api/clients*' => Http::response(['data' => []]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');

        $client = HelaSdkFacade::auster();
        $client->offers(filter: 'Plan', status: 1, product: 'MBB', serviceType: ['PRE'], allowsNewLineActivation: false);
        $client->clients(['filter' => 'old', 'passthrough' => 'yes'], filter: 'Acme', type: 'corporate');
        $client->clientServices('CLI-1', filter: '5255', product: 'MBB', serviceType: 'PRE', status: 'ACTIVE', groupId: 7, sort: 'name', direction: 'desc');

        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return parse_url($request->url(), PHP_URL_PATH) === '/api/catalogs/offers'
                && ($query['filter'] ?? null) === 'Plan'
                && ($query['status'] ?? null) === '1'
                && ($query['product'] ?? null) === 'MBB'
                && ($query['service_type'][0] ?? null) === 'PRE'
                && (string) ($query['allows_new_line_activation'] ?? '') === '0';
        });
        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return parse_url($request->url(), PHP_URL_PATH) === '/api/clients'
                && ($query['filter'] ?? null) === 'Acme'
                && ($query['passthrough'] ?? null) === 'yes'
                && ($query['type'] ?? null) === 'corporate';
        });
        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return parse_url($request->url(), PHP_URL_PATH) === '/api/clients/CLI-1/services'
                && ($query['filter'] ?? null) === '5255'
                && ($query['product'] ?? null) === 'MBB'
                && ($query['service_type'] ?? null) === 'PRE'
                && ($query['status'] ?? null) === 'ACTIVE'
                && ($query['group_id'] ?? null) === '7'
                && ($query['sort'] ?? null) === 'name'
                && ($query['direction'] ?? null) === 'desc';
        });
        Http::assertSentCount(3);
    }

    public function test_auster_clients_api_list_methods_accept_named_query_parameters(): void
    {
        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH);

            return match ($path) {
                '/clients-api/catalogs/offers' => Http::response(['data' => [['offer_id' => 'OFFER-1']]]),
                '/clients-api/orders' => Http::response(['data' => [['id_order' => 501]]]),
                '/clients-api/services' => Http::response(['data' => [['id_service' => 10]]]),
                '/clients-api/service-groups' => Http::response(['data' => [['id_serviceGroup' => 3]]]),
                '/clients-api/users' => Http::response(['data' => [['uri_clientUser' => 'USR-1']]]),
                default => Http::response(['data' => [['id' => 1]]]),
            };
        });

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');

        $client = HelaSdkFacade::auster()->clientsApiAsClient('client-token');
        $client->simCards(filter: 'ICCID', product: 'MBB', type: 'physical', status: 1, paginate: false, page: 2, perPage: 15);
        $client->invoices(['filter' => 'old'], filter: 'Factura', year: 2026, month: 6, paginate: true, page: 1, perPage: 20);
        $client->catalogOffers(filter: 'Plan', status: '1', product: 'MBB', serviceType: 'PRE', allowsNewLineActivation: true);
        $client->cfdi(filter: 'UUID', year: '2026', month: '06', paginate: true, page: 3, perPage: 25);
        $client->orders(filter: '5551234567', type: 'TOPUP', dateFrom: '2026-06-01', dateTo: '2026-06-30', paginate: true, page: 2, perPage: 15);
        $client->services(filter: '5255', product: ['MBB'], serviceType: ['PRE'], status: ['ACTIVE'], imei: '35988', groupId: 7, onlyActive: false, paginate: true, page: 4, perPage: 50, sort: 'expiry_date', direction: 'desc');
        $client->serviceGroups(filter: 'Operaciones', paginate: true, page: 1, perPage: 10);
        $client->users(filter: 'admin@example.test', paginate: true, page: 1, perPage: 10);

        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return parse_url($request->url(), PHP_URL_PATH) === '/clients-api/sim-cards'
                && ($query['filter'] ?? null) === 'ICCID'
                && ($query['product'] ?? null) === 'MBB'
                && ($query['type'] ?? null) === 'physical'
                && ($query['status'] ?? null) === '1'
                && (string) ($query['paginate'] ?? '') === '0'
                && ($query['page'] ?? null) === '2'
                && ($query['per_page'] ?? null) === '15';
        });
        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return parse_url($request->url(), PHP_URL_PATH) === '/clients-api/accounting/invoices'
                && ($query['filter'] ?? null) === 'Factura'
                && ($query['year'] ?? null) === '2026'
                && ($query['month'] ?? null) === '6'
                && ($query['per_page'] ?? null) === '20';
        });
        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return parse_url($request->url(), PHP_URL_PATH) === '/clients-api/catalogs/offers'
                && ($query['filter'] ?? null) === 'Plan'
                && ($query['service_type'] ?? null) === 'PRE'
                && (string) ($query['allows_new_line_activation'] ?? '') === '1'
                && ! array_key_exists('search', $query);
        });
        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return parse_url($request->url(), PHP_URL_PATH) === '/clients-api/cfdi'
                && ($query['filter'] ?? null) === 'UUID'
                && ($query['year'] ?? null) === '2026'
                && ($query['month'] ?? null) === '06'
                && ($query['page'] ?? null) === '3'
                && ($query['per_page'] ?? null) === '25';
        });
        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return parse_url($request->url(), PHP_URL_PATH) === '/clients-api/orders'
                && ($query['filter'] ?? null) === '5551234567'
                && ($query['type'] ?? null) === 'TOPUP'
                && ($query['date_from'] ?? null) === '2026-06-01'
                && ($query['date_to'] ?? null) === '2026-06-30'
                && ($query['per_page'] ?? null) === '15';
        });
        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return parse_url($request->url(), PHP_URL_PATH) === '/clients-api/services'
                && ($query['filter'] ?? null) === '5255'
                && ($query['product'][0] ?? null) === 'MBB'
                && ($query['service_type'][0] ?? null) === 'PRE'
                && ($query['status'][0] ?? null) === 'ACTIVE'
                && ($query['imei'] ?? null) === '35988'
                && ($query['group_id'] ?? null) === '7'
                && (string) ($query['only_active'] ?? '') === '0'
                && ($query['sort'] ?? null) === 'expiry_date'
                && ($query['direction'] ?? null) === 'desc';
        });
        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return parse_url($request->url(), PHP_URL_PATH) === '/clients-api/service-groups'
                && ($query['filter'] ?? null) === 'Operaciones'
                && ($query['per_page'] ?? null) === '10';
        });
        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return parse_url($request->url(), PHP_URL_PATH) === '/clients-api/users'
                && ($query['filter'] ?? null) === 'admin@example.test'
                && ($query['per_page'] ?? null) === '10';
        });
        Http::assertSentCount(8);
    }

    public function test_auster_clients_api_exposes_instance_user_sync_routes(): void
    {
        Http::fake([
            'https://auster.example.test/clients-api/instances/create/user' => Http::response([
                'data' => [
                    'uri_clientUser' => 'user-1',
                    'email' => 'user@example.test',
                    'name' => 'Test User',
                ],
            ], 201),
            'https://auster.example.test/clients-api/instances/update/user/user%40example.test' => Http::response([
                'data' => [
                    'uri_clientUser' => 'user-1',
                    'email' => 'user@example.test',
                    'name' => 'Updated User',
                ],
            ]),
            'https://auster.example.test/clients-api/instances/delete/user/user%40example.test' => Http::response([
                'message' => 'Usuario eliminado correctamente',
            ]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');

        $client = HelaSdkFacade::auster()->clientsApiAsClient('client-token');
        $created = $client->createInstanceUser([
            'email' => 'user@example.test',
            'name' => 'Test User',
            'password' => 'secret',
        ]);
        $updated = $client->updateInstanceUserByEmail('user@example.test', [
            'name' => 'Updated User',
        ]);
        $deleted = $client->deleteInstanceUserByEmail('user@example.test');

        $this->assertInstanceOf(UserProfileDto::class, $created);
        $this->assertSame('user@example.test', $created->email);
        $this->assertSame('Updated User', $updated->name);
        $this->assertSame('Usuario eliminado correctamente', $deleted->message);
        Http::assertSentCount(3);
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

    public function test_auster_clients_api_preserves_current_offer_and_service_contract_fields(): void
    {
        Http::fake([
            'https://auster.example.test/clients-api/catalogs/offers*' => Http::response([
                'data' => [
                    [
                        'offer_id' => 'HLA-20',
                        'public_name' => 'Plan 20',
                        'allows_new_line_activation' => true,
                        'commission_enabled' => true,
                        'commission_activation_amount' => 35,
                        'commission_retention_enabled' => false,
                    ],
                ],
            ]),
            'https://auster.example.test/clients-api/services*' => Http::response([
                'data' => [
                    [
                        'id_service' => 10,
                        'id_client' => 'CLI-1',
                        'client_name' => 'Acme',
                        'name' => 'Linea comercial',
                        'msisdn' => '525512345678',
                        'service_type' => 'Prepago',
                        'service_type_code' => 'PRE',
                        'status_label' => 'Activo',
                        'status_variant' => 'success',
                        'is_linked' => true,
                        'requires_linking' => false,
                        'linking' => ['status' => 'linked'],
                        'offer_name' => 'Plan 20',
                        'product' => 'MBB',
                        'dt_expiry' => '2026-06-30',
                        'expiry_days' => 25,
                        'consumption_summary' => ['items' => []],
                    ],
                ],
            ]),
        ]);

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');

        $client = HelaSdkFacade::auster()->clientsApiAsClient('client-token');
        $offers = $client->catalogOffers(['filter' => 'Plan 20', 'status' => '1']);
        $services = $client->services(['filter' => '525512345678', 'paginate' => false]);

        $this->assertInstanceOf(OfferDto::class, $offers->first());
        $this->assertTrue($offers->first()->allowsNewLineActivation);
        $this->assertTrue($offers->first()->commissionEnabled);
        $this->assertSame(35.0, $offers->first()->commissionActivationAmount);
        $this->assertFalse($offers->first()->commissionRetentionEnabled);
        $this->assertInstanceOf(ServiceDto::class, $services->first());
        $this->assertSame('Acme', $services->first()->clientName);
        $this->assertSame('PRE', $services->first()->serviceTypeCode);
        $this->assertSame('success', $services->first()->statusVariant);
        $this->assertTrue($services->first()->isLinked);
        $this->assertSame('linked', $services->first()->linking['status']);
        $this->assertSame('2026-06-30', $services->first()->dtExpiry);
        $this->assertSame(25, $services->first()->expiryDays);
        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return parse_url($request->url(), PHP_URL_PATH) === '/clients-api/catalogs/offers'
                && ($query['filter'] ?? null) === 'Plan 20'
                && ($query['status'] ?? null) === '1'
                && ! array_key_exists('search', $query);
        });
        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return parse_url($request->url(), PHP_URL_PATH) === '/clients-api/services'
                && ($query['filter'] ?? null) === '525512345678'
                && in_array((string) ($query['paginate'] ?? ''), ['', '0'], true)
                && ! array_key_exists('search', $query);
        });
        Http::assertSentCount(2);
    }

    public function test_auster_clients_api_exposes_account_documents_tax_wallet_and_order_payment_routes(): void
    {
        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            $method = $request->method();

            return match ([$method, $path]) {
                ['POST', '/clients-api/orders/501/payments'] => Http::response([
                    'message' => 'Payment added',
                    'data' => ['id_payment' => 9],
                ], 201),
                ['GET', '/clients-api/wallet'] => Http::response([
                    'data' => ['balance' => 150.25, 'currency' => 'MXN'],
                ]),
                ['GET', '/clients-api/wallet/transactions'] => Http::response([
                    'data' => [['id_transaction' => 1, 'amount' => 25]],
                ]),
                ['GET', '/clients-api/documents'] => Http::response([
                    'data' => [['document_key' => 'csf', 'status' => 'pending']],
                ]),
                ['POST', '/clients-api/documents/csf'] => Http::response([
                    'data' => ['document_key' => 'csf', 'status' => 'uploaded'],
                ], 201),
                ['GET', '/clients-api/documents/csf/versions'] => Http::response([
                    'data' => [['id_document' => 12, 'version' => 1]],
                ]),
                ['GET', '/clients-api/documents/csf/versions/12/download'] => Http::response('pdf-bytes'),
                ['GET', '/clients-api/tax-profiles'] => Http::response([
                    'data' => [['uid' => 'tax-1', 'rfc' => 'XAXX010101000']],
                ]),
                ['POST', '/clients-api/tax-profiles'] => Http::response([
                    'data' => ['uid' => 'tax-2', 'rfc' => 'XEXX010101000'],
                ], 201),
                ['GET', '/clients-api/tax-profiles/catalogs'] => Http::response([
                    'data' => ['regimes' => [['value' => '601']]],
                ]),
                ['GET', '/clients-api/tax-profiles/tax-1'] => Http::response([
                    'data' => ['uid' => 'tax-1', 'rfc' => 'XAXX010101000'],
                ]),
                ['PUT', '/clients-api/tax-profiles/tax-1'] => Http::response([
                    'data' => ['uid' => 'tax-1', 'rfc' => 'XAXX010101000', 'name' => 'Updated'],
                ]),
                ['DELETE', '/clients-api/tax-profiles/tax-1'] => Http::response([
                    'message' => 'Tax profile deleted',
                ]),
                default => Http::response(['message' => 'Unexpected request'], 404),
            };
        });

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');
        $client = HelaSdkFacade::auster()->clientsApiAsClient('client-token');

        $payment = $client->addOrderPayment(501, ['amount' => 99]);
        $wallet = $client->wallet();
        $transactions = $client->walletTransactions();
        $documents = $client->documents();
        $storedDocument = $client->storeDocument('csf', ['file' => 'contents']);
        $versions = $client->documentVersions('csf');
        $download = $client->downloadDocument('csf', 12);
        $taxProfiles = $client->taxProfiles();
        $createdTaxProfile = $client->createTaxProfile(['rfc' => 'XEXX010101000']);
        $taxCatalogs = $client->taxProfileCatalogs();
        $taxProfile = $client->taxProfile('tax-1');
        $updatedTaxProfile = $client->updateTaxProfile('tax-1', ['name' => 'Updated']);
        $deletedTaxProfile = $client->deleteTaxProfile('tax-1');

        $this->assertSame('Payment added', $payment->message);
        $this->assertSame(150.25, $wallet->balance);
        $this->assertSame(25, $transactions->first()->amount);
        $this->assertSame('csf', $documents->first()->document_key);
        $this->assertSame('uploaded', $storedDocument->status);
        $this->assertSame(12, $versions->first()->id_document);
        $this->assertSame('pdf-bytes', $download->body());
        $this->assertSame('tax-1', $taxProfiles->first()->uid);
        $this->assertSame('tax-2', $createdTaxProfile->uid);
        $this->assertSame('601', $taxCatalogs->regimes[0]['value']);
        $this->assertSame('XAXX010101000', $taxProfile->rfc);
        $this->assertSame('Updated', $updatedTaxProfile->name);
        $this->assertSame('Tax profile deleted', $deletedTaxProfile->message);
        Http::assertSentCount(13);
    }

    public function test_auster_clients_api_exposes_service_groups_and_bulk_routes(): void
    {
        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            $method = $request->method();

            return match ([$method, $path]) {
                ['GET', '/clients-api/catalogs/payment-methods'] => Http::response([
                    'data' => [['value' => 'SPEI', 'label' => 'Transferencia']],
                ]),
                ['GET', '/clients-api/service-groups'] => Http::response([
                    'data' => [[
                        'id_serviceGroup' => 3,
                        'name' => 'Operaciones',
                        'icon' => 'briefcase',
                        'services_count' => 5,
                    ]],
                ]),
                ['POST', '/clients-api/service-groups'] => Http::response([
                    'data' => ['id_serviceGroup' => 4, 'name' => 'Ventas', 'icon' => 'users'],
                ], 201),
                ['PATCH', '/clients-api/service-groups/4'] => Http::response([
                    'data' => ['id_serviceGroup' => 4, 'name' => 'Ventas MX', 'icon' => 'users'],
                ]),
                ['DELETE', '/clients-api/service-groups/4'] => Http::response([
                    'message' => 'Grupo eliminado correctamente',
                ]),
                ['PUT', '/clients-api/service-groups/3/services'] => Http::response([
                    'data' => ['assigned_ids' => [10, 11]],
                ]),
                ['POST', '/clients-api/services/bulk-actions/capabilities'] => Http::response([
                    'ok' => true,
                    'capabilities' => ['actions' => [['id' => 'suspend', 'available' => true]]],
                ]),
                ['POST', '/clients-api/services/bulk-actions/preview'] => Http::response([
                    'ok' => true,
                    'preview' => ['action' => 'suspend', 'eligible_count' => 2],
                ]),
                ['POST', '/clients-api/services/bulk-actions'] => Http::response([
                    'ok' => true,
                    'operation' => ['id_serviceBulkOperation' => 77, 'action' => 'suspend', 'status' => 'queued'],
                ], 202),
                ['GET', '/clients-api/services/bulk-operations/77'] => Http::response([
                    'ok' => true,
                    'operation' => ['id_serviceBulkOperation' => 77, 'status' => 'completed', 'is_terminal' => true],
                ]),
                ['POST', '/clients-api/services/bulk-operations/77/retry'] => Http::response([
                    'ok' => true,
                    'operation' => ['id_serviceBulkOperation' => 77, 'status' => 'queued'],
                ], 202),
                ['GET', '/clients-api/services/bulk-operations/latest'] => Http::response([
                    'ok' => true,
                    'operation' => ['id_serviceBulkOperation' => 78, 'status' => 'running'],
                ]),
                default => Http::response(['message' => 'Unexpected request'], 404),
            };
        });

        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');
        $client = HelaSdkFacade::auster()->clientsApiAsClient('client-token');

        $paymentMethods = $client->paymentMethods();
        $groups = $client->serviceGroups();
        $created = $client->createServiceGroup(['name' => 'Ventas', 'icon' => 'users']);
        $updated = $client->updateServiceGroup(4, ['name' => 'Ventas MX']);
        $deleted = $client->deleteServiceGroup(4);
        $sync = $client->syncServiceGroupServices(3, [10, 11]);
        $capabilities = $client->serviceBulkCapabilities(['selection' => ['mode' => 'ids', 'ids' => [10, 11]]]);
        $preview = $client->previewServiceBulkAction(['action' => 'suspend', 'selection' => ['mode' => 'ids', 'ids' => [10, 11]]]);
        $stored = $client->storeServiceBulkAction(['action' => 'suspend', 'confirmed' => true, 'selection' => ['mode' => 'ids', 'ids' => [10, 11]]]);
        $operation = $client->serviceBulkOperation(77);
        $retry = $client->retryServiceBulkOperation(77);
        $latest = $client->latestServiceBulkOperation();

        $this->assertInstanceOf(DtoCollection::class, $paymentMethods);
        $this->assertSame('SPEI', $paymentMethods->first()->value);
        $this->assertInstanceOf(ServiceGroupDto::class, $groups->first());
        $this->assertSame(3, $groups->first()->idServiceGroup);
        $this->assertSame('Ventas', $created->name);
        $this->assertSame('Ventas MX', $updated->name);
        $this->assertSame('Grupo eliminado correctamente', $deleted->message);
        $this->assertSame([10, 11], $sync->assigned_ids);
        $this->assertSame('suspend', $capabilities->capabilities['actions'][0]['id']);
        $this->assertSame(2, $preview->preview['eligible_count']);
        $this->assertInstanceOf(ServiceBulkOperationDto::class, $stored);
        $this->assertSame(77, $stored->idServiceBulkOperation);
        $this->assertTrue($operation->isTerminal);
        $this->assertSame('queued', $retry->status);
        $this->assertSame(78, $latest->idServiceBulkOperation);
        Http::assertSentCount(12);
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
