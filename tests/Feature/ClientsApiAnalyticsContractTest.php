<?php

namespace Ometra\HelaSdk\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Ometra\HelaSdk\Dtos\DashboardDto;
use Ometra\HelaSdk\Dtos\OfferDto;
use Ometra\HelaSdk\Dtos\ReportDto;
use Ometra\HelaSdk\Facades\HelaSdk;
use Ometra\HelaSdk\Tests\TestCase;

final class ClientsApiAnalyticsContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('hela-sdk.auster.base_url', 'https://auster.example.test');
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
