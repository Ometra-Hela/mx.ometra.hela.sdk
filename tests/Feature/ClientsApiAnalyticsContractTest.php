<?php

namespace Ometra\HelaSdk\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Ometra\HelaSdk\Dtos\DashboardDto;
use Ometra\HelaSdk\Dtos\OfferDto;
use Ometra\HelaSdk\Dtos\ReportDto;
use Ometra\HelaSdk\Dtos\GenericDto;
use Ometra\HelaSdk\Facades\HelaSdk;
use Ometra\HelaSdk\Tests\TestCase;

final class ClientsApiAnalyticsContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');
    }

    public function test_monthly_consumption_and_group_assignment_use_client_scoped_routes(): void
    {
        Http::fake([
            'https://auster.example.test/clients-api/services/consumption/monthly*' => Http::response(['data' => [
                'month' => '2026-09', 'total_data_mb' => 125.5,
                'services' => [['id_service' => 10, 'data_mb' => 125.5]],
            ]]),
            'https://auster.example.test/clients-api/services/5511111111/consumption/monthly*' => Http::response(['data' => [
                'id_service' => 10, 'data_mb' => 125.5, 'daily' => [['date' => '2026-09-01', 'data_mb' => 125.5]],
            ]]),
            'https://auster.example.test/clients-api/service-groups/selection' => Http::response(['data' => ['updated_count' => 1]]),
        ]);

        $client = HelaSdk::auster()->clientsApiAsClient('client-token');
        $usage = $client->monthlyServiceConsumption('2026-09');
        $serviceUsage = $client->monthlyConsumptionForService('5511111111', '2026-09');
        $assigned = $client->assignServiceGroupSelection([10], null);

        $this->assertInstanceOf(GenericDto::class, $usage);
        $this->assertSame(125.5, $usage->get('total_data_mb'));
        $this->assertSame(125.5, $serviceUsage->get('data_mb'));
        $this->assertSame(1, $assigned->get('updated_count'));
        Http::assertSent(fn ($request): bool => $request->method() === 'GET'
            && $request->url() === 'https://auster.example.test/clients-api/services/consumption/monthly?month=2026-09');
        Http::assertSent(fn ($request): bool => $request->method() === 'PUT'
            && parse_url($request->url(), PHP_URL_PATH) === '/clients-api/service-groups/selection'
            && $request['service_ids'] === [10]
            && $request['group_id'] === null);
    }

    public function test_effective_catalog_fixture_is_typed_and_keeps_public_price_compatibility(): void
    {
        Http::fake([
            'https://auster.example.test/clients-api/catalogs/offers*' => Http::response($this->fixture('catalog-offers')),
        ]);

        $offer = HelaSdk::auster()->clientsApiAsClient('client-token')->catalogOffers()->first();

        $this->assertInstanceOf(OfferDto::class, $offer);
        $this->assertSame(120.0, $offer->publicPrice);
        $this->assertSame(120.0, $offer->listPrice);
        $this->assertSame(99.5, $offer->effectivePrice);
        $this->assertTrue($offer->hasClientPrice);
        $this->assertTrue($offer->purchasable);
        $this->assertSame([
            'activation' => true,
            'renewal' => true,
            'topup' => false,
            'purchase' => true,
        ], $offer->capabilities);
    }

    public function test_dashboard_fixture_and_period_query_are_typed(): void
    {
        Http::fake([
            'https://auster.example.test/clients-api/dashboard*' => Http::response($this->fixture('dashboard')),
        ]);

        $dashboard = HelaSdk::auster()->clientsApiAsClient('client-token')->dashboard('30d');

        $this->assertInstanceOf(DashboardDto::class, $dashboard);
        $this->assertSame('30d', $dashboard->period);
        $this->assertSame(42, $dashboard->kpis['active_services']);
        $this->assertSame('service.activated', $dashboard->recentActivity[0]['action']);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://auster.example.test/clients-api/dashboard?period=30d');
    }

    public function test_report_fixture_and_filters_are_typed(): void
    {
        Http::fake([
            'https://auster.example.test/clients-api/reports/spending*' => Http::response($this->fixture('report-spending')),
        ]);

        $report = HelaSdk::auster()->clientsApiAsClient('client-token')->report(
            'spending',
            '2026-07-01',
            '2026-07-31',
            'day',
        );

        $this->assertInstanceOf(ReportDto::class, $report);
        $this->assertSame('spending', $report->type);
        $this->assertSame('day', $report->groupBy);
        $this->assertSame(4250.5, $report->summary['total']);
        $this->assertSame('topups', $report->breakdown[0]['key']);
        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return parse_url($request->url(), PHP_URL_PATH) === '/clients-api/reports/spending'
                && $query === [
                    'from' => '2026-07-01',
                    'to' => '2026-07-31',
                    'group_by' => 'day',
                ];
        });
    }

    /** @return array<string, mixed> */
    private function fixture(string $name): array
    {
        $contents = file_get_contents(__DIR__ . '/../Fixtures/' . $name . '.json');
        $this->assertNotFalse($contents);

        return json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
    }
}
