<?php

namespace zfhassaan\genlytics\Tests\Unit;

use Illuminate\Http\JsonResponse;
use Mockery;
use zfhassaan\genlytics\Genlytics;
use zfhassaan\genlytics\Services\AnalyticsService;
use zfhassaan\genlytics\Tests\TestCase;

class GenlyticsTest extends TestCase
{
    protected $mockService;
    protected Genlytics $genlytics;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockService = Mockery::mock(AnalyticsService::class);
        $this->genlytics = new Genlytics($this->mockService);
        $this->app->instance('genlytics', $this->genlytics);
    }

    public function test_can_run_reports()
    {
        $period = ['start_date' => '7daysAgo', 'end_date' => 'today'];
        $dimension = [['name' => 'country']];
        $metrics = [['name' => 'activeUsers']];

        $mockResponse = $this->createMock(JsonResponse::class);

        $this->mockService
            ->shouldReceive('runReports')
            ->once()
            ->with($period, $dimension, $metrics, [], false)
            ->andReturn($mockResponse);

        $result = $this->genlytics->runReports($period, $dimension, $metrics);

        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    public function test_can_run_realtime()
    {
        $dimension = [['name' => 'country']];
        $metrics = [['name' => 'activeUsers']];

        $mockResponse = $this->createMock(JsonResponse::class);

        $this->mockService
            ->shouldReceive('runRealTime')
            ->once()
            ->with($dimension, $metrics, [], false)
            ->andReturn($mockResponse);

        $result = $this->genlytics->runRealTime($dimension, $metrics);

        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    public function test_can_run_dimension_report()
    {
        $period = ['start_date' => '7daysAgo', 'end_date' => 'today'];
        $dimension = 'country';

        $mockResponse = $this->createMock(JsonResponse::class);

        $this->mockService
            ->shouldReceive('runDimensionReport')
            ->once()
            ->with($period, $dimension, [], false)
            ->andReturn($mockResponse);

        $result = $this->genlytics->runDimensionReport($period, $dimension);

        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    public function test_normalizes_single_dimension_to_array()
    {
        $period = ['start_date' => '7daysAgo', 'end_date' => 'today'];
        $dimension = ['name' => 'country'];
        $metrics = [['name' => 'activeUsers']];

        $mockResponse = $this->createMock(JsonResponse::class);

        $this->mockService
            ->shouldReceive('runReports')
            ->once()
            ->with($period, [['name' => 'country']], $metrics, [], false)
            ->andReturn($mockResponse);

        $result = $this->genlytics->runReports($period, $dimension, $metrics);

        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    public function test_can_get_service()
    {
        $service = $this->genlytics->getService();
        $this->assertSame($this->mockService, $service);
    }

    public function test_can_query_top_products()
    {
        $mockData = [
            [
                'dimensions' => ['itemId' => 'SKU1', 'itemName' => 'Keyboard', 'itemCategory' => 'Tech'],
                'metrics' => ['itemRevenue' => 99.99, 'itemsPurchased' => 3, 'itemsViewed' => 50, 'itemsAddedToCart' => 10],
            ],
        ];

        $this->mockService
            ->shouldReceive('getReport')
            ->once()
            ->andReturn($mockData);

        $products = $this->genlytics->topProducts(limit: 5, ttl: 3600);
        $this->assertCount(1, $products);
        $this->assertEquals('Keyboard', $products->first()->name);
    }

    public function test_can_query_top_categories()
    {
        $mockData = [
            [
                'dimensions' => ['itemCategory' => 'Tech'],
                'metrics' => ['itemRevenue' => 500.0, 'itemsPurchased' => 15, 'itemsViewed' => 200, 'itemsAddedToCart' => 40],
            ],
        ];

        $this->mockService
            ->shouldReceive('getReport')
            ->once()
            ->andReturn($mockData);

        $categories = $this->genlytics->topCategories(limit: 5);
        $this->assertCount(1, $categories);
        $this->assertEquals('Tech', $categories->first()->category);
    }

    public function test_can_query_ecommerce_funnel()
    {
        $mockData = [
            ['dimensions' => ['eventName' => 'view_item'], 'metrics' => ['eventCount' => 100]],
            ['dimensions' => ['eventName' => 'add_to_cart'], 'metrics' => ['eventCount' => 50]],
            ['dimensions' => ['eventName' => 'purchase'], 'metrics' => ['eventCount' => 25]],
        ];

        $this->mockService
            ->shouldReceive('getReport')
            ->once()
            ->andReturn($mockData);

        $funnel = $this->genlytics->ecommerceFunnel();
        $this->assertEquals('E-Commerce Purchase Funnel', $funnel->name);
        $this->assertEquals(100, $funnel->totalEntries);
        $this->assertEquals(25, $funnel->totalCompletions);
        $this->assertEquals(25.0, $funnel->overallConversionRate);
    }

    public function test_can_enable_all_quota_consumption()
    {
        $mockQuota = Mockery::mock(\zfhassaan\genlytics\Contracts\QuotaManagerInterface::class);
        $mockQuota->shouldReceive('enableAllQuotaConsumption')->once()->andReturnSelf();
        $mockQuota->shouldReceive('shouldConsumeAllDailyQuota')->once()->andReturn(true);
        $this->mockService->shouldReceive('quota')->andReturn($mockQuota);

        $this->genlytics->enableAllQuotaConsumption();
        $this->assertTrue($this->genlytics->isAllQuotaConsumptionEnabled());
    }

    public function test_can_consume_all_daily_quota()
    {
        $mockQuota = Mockery::mock(\zfhassaan\genlytics\Contracts\QuotaManagerInterface::class);
        $mockQuota->shouldReceive('setConsumeAllDailyQuota')->with(true)->once()->andReturnSelf();
        $mockQuota->shouldReceive('shouldConsumeAllDailyQuota')->once()->andReturn(true);
        $this->mockService->shouldReceive('quota')->andReturn($mockQuota);

        $this->genlytics->consumeAllDailyQuota(true);
        $this->assertTrue($this->genlytics->isAllQuotaConsumptionEnabled());
    }

    public function test_drain_daily_quota()
    {
        $mockQuota = Mockery::mock(\zfhassaan\genlytics\Contracts\QuotaManagerInterface::class);
        $mockQuota->shouldReceive('drainDailyQuota')->once()->andReturn([
            'total_consumed' => 100,
            'operations_executed' => 2,
        ]);
        $this->mockService->shouldReceive('quota')->andReturn($mockQuota);

        $report = $this->genlytics->drainDailyQuota(fn () => 50, 100);
        $this->assertEquals(100, $report['total_consumed']);
        $this->assertEquals(2, $report['operations_executed']);
    }

    public function test_can_get_and_set_cache_ttl()
    {
        $this->mockService->shouldReceive('setCacheTtl')->with(3600, 'report')->once();
        $this->mockService->shouldReceive('getCacheTtl')->with('report')->once()->andReturn(3600);

        $this->genlytics->setCacheTtl(3600, 'report');
        $this->assertEquals(3600, $this->genlytics->getCacheTtl('report'));
    }

    public function test_can_access_cache_manager()
    {
        $mockCacheManager = Mockery::mock(\zfhassaan\genlytics\Contracts\CacheManagerInterface::class);
        $this->mockService->shouldReceive('getCacheManager')->once()->andReturn($mockCacheManager);

        $this->assertSame($mockCacheManager, $this->genlytics->cache());
    }

    public function test_dedicated_reporting_helpers()
    {
        $mockRow = [
            'dimensions' => ['pagePath' => '/home', 'city' => 'NY', 'country' => 'US', 'sessionSource' => 'google', 'deviceCategory' => 'mobile', 'browser' => 'Chrome', 'eventName' => 'signup', 'date' => '2026-10-25'],
            'metrics' => ['activeUsers' => 120, 'sessions' => 150, 'screenPageViews' => 300, 'newUsers' => 50, 'engagedSessions' => 90, 'engagementRate' => 0.6, 'userEngagementDuration' => 45.0, 'bounceRate' => 0.25, 'eventCount' => 10, 'conversions' => 8],
        ];

        $this->mockService->shouldReceive('getReport')->andReturn([$mockRow]);

        $this->assertNotEmpty($this->genlytics->topPages(5));
        $this->assertNotEmpty($this->genlytics->topCountries(5));
        $this->assertNotEmpty($this->genlytics->topCities(5));
        $this->assertNotEmpty($this->genlytics->trafficSources(5));
        $this->assertNotEmpty($this->genlytics->userEngagement());
        $this->assertNotEmpty($this->genlytics->deviceCategories());
        $this->assertNotEmpty($this->genlytics->topBrowsers(5));
        $this->assertNotEmpty($this->genlytics->conversions(5));
        $this->assertNotEmpty($this->genlytics->dailyTrend(7));
        $this->assertEquals(120, $this->genlytics->activeUsers());
        $this->assertEquals(150, $this->genlytics->totalSessions());
    }

    public function test_dedicated_realtime_helpers()
    {
        $mockRow = [
            'dimensions' => ['pagePath' => '/live', 'country' => 'US'],
            'metrics' => ['activeUsers' => 42],
        ];

        $this->mockService->shouldReceive('getRealTime')->andReturn([$mockRow]);

        $this->assertEquals(42, $this->genlytics->realtimeActiveUsers());
        $this->assertNotEmpty($this->genlytics->realtimeTopPages(5));
        $this->assertNotEmpty($this->genlytics->realtimeTopCountries(5));
    }

    public function test_batch_reports_and_get_report()
    {
        $this->mockService->shouldReceive('getReport')->once()->andReturn([['dimensions' => []]]);
        $this->assertNotEmpty($this->genlytics->getReport(['start_date' => '7daysAgo', 'end_date' => 'today'], 'country', 'activeUsers'));

        $this->mockService->shouldReceive('batchRunReports')->once()->andReturn(new JsonResponse(['batch' => true]));
        $resp = $this->genlytics->batchRunReports([['dimensions' => ['country'], 'metrics' => ['activeUsers']]]);
        $this->assertInstanceOf(JsonResponse::class, $resp);
    }

    public function test_quota_mode_helpers()
    {
        $mockQuota = Mockery::mock(\zfhassaan\genlytics\Contracts\QuotaManagerInterface::class);
        $mockQuota->shouldReceive('setMode')->with('maximum_utilization')->once();
        $mockQuota->shouldReceive('getMode')->once()->andReturn('maximum_utilization');
        $mockQuota->shouldReceive('isMaximumUtilization')->once()->andReturn(true);
        $this->mockService->shouldReceive('quota')->andReturn($mockQuota);
        $this->genlytics->setQuotaMode('maximum_utilization');
        $this->assertEquals('maximum_utilization', $this->genlytics->getQuotaMode());
        $this->assertTrue($this->genlytics->isMaximumQuota());
    }

    public function test_facade_version_and_resource_helpers()
    {
        $this->assertEquals('1.1.0', \zfhassaan\genlytics\facades\AnalyticsFacade::version());

        $mockData = [
            [
                'dimensions' => ['country' => 'Canada', 'itemCategory' => 'Books'],
                'metrics' => ['activeUsers' => 15, 'itemRevenue' => 120.0, 'itemsPurchased' => 4],
            ],
        ];

        $this->mockService->shouldReceive('getReport')->twice()->andReturn($mockData);

        $countriesRes = $this->genlytics->topCountriesResource(limit: 5);
        $this->assertEquals('1.1.0', $countriesRes->toResponse(\Illuminate\Http\Request::create('/test'))->getData(true)['version']);

        $categoriesRes = $this->genlytics->topCategoriesResource(limit: 5);
        $this->assertEquals('1.1.0', $categoriesRes->toResponse(\Illuminate\Http\Request::create('/test'))->getData(true)['version']);
    }

    public function test_funnel_and_quota_resource_helpers()
    {
        $mockData = [
            ['dimensions' => ['eventName' => 'view_item'], 'metrics' => ['eventCount' => 100]],
            ['dimensions' => ['eventName' => 'purchase'], 'metrics' => ['eventCount' => 10]],
        ];

        $this->mockService->shouldReceive('getReport')->once()->andReturn($mockData);

        $funnelRes = $this->genlytics->ecommerceFunnelResource();
        $this->assertInstanceOf(\zfhassaan\genlytics\Http\Resources\FunnelPipelineResource::class, $funnelRes);
        $this->assertEquals('1.1.0', $funnelRes->toResponse(\Illuminate\Http\Request::create('/test'))->getData(true)['version']);

        $mockQuota = Mockery::mock(\zfhassaan\genlytics\Contracts\QuotaManagerInterface::class);
        $mockQuota->shouldReceive('getStatus')->once()->andReturn(new \zfhassaan\genlytics\DTOs\QuotaStatusDTO(['property_type' => 'standard']));
        $this->mockService->shouldReceive('quota')->once()->andReturn($mockQuota);

        $quotaRes = $this->genlytics->quotaStatusResource();
        $this->assertInstanceOf(\zfhassaan\genlytics\Http\Resources\QuotaStatusResource::class, $quotaRes);
        $this->assertEquals('1.1.0', $quotaRes->toResponse(\Illuminate\Http\Request::create('/test'))->getData(true)['version']);
    }

    public function test_additional_resource_helpers_and_telemetry()
    {
        $mockData = [
            [
                'dimensions' => ['pagePath' => '/home', 'pageTitle' => 'Home', 'sessionSource' => 'google', 'sessionMedium' => 'cpc', 'itemId' => '1', 'itemName' => 'Item', 'itemCategory' => 'Cat'],
                'metrics' => ['screenPageViews' => 100, 'activeUsers' => 50, 'averageSessionDuration' => 60.0, 'sessions' => 40, 'bounceRate' => 0.2, 'itemRevenue' => 200.0, 'itemsPurchased' => 5, 'itemsViewed' => 50, 'itemsAddedToCart' => 10],
            ],
        ];

        $this->mockService->shouldReceive('getReport')->times(3)->andReturn($mockData);

        // topPagesResource
        $pagesRes = $this->genlytics->topPagesResource(5);
        $this->assertEquals('1.1.0', $pagesRes->toResponse(\Illuminate\Http\Request::create('/test'))->getData(true)['version']);

        // trafficSourcesResource
        $trafficRes = $this->genlytics->trafficSourcesResource(5);
        $this->assertEquals('1.1.0', $trafficRes->toResponse(\Illuminate\Http\Request::create('/test'))->getData(true)['version']);

        // topProductsResource
        $prodRes = $this->genlytics->topProductsResource(5);
        $this->assertEquals('1.1.0', $prodRes->toResponse(\Illuminate\Http\Request::create('/test'))->getData(true)['version']);

        // getBatchReports
        $this->mockService->shouldReceive('getBatchReports')->once()->andReturn([$mockData]);
        $batch = $this->genlytics->getBatchReports([['dimensions' => ['country'], 'metrics' => ['activeUsers']]]);
        $this->assertCount(1, $batch);

        // getDimensionReport & getRealTime
        $this->mockService->shouldReceive('getDimensionReport')->once()->andReturn($mockData);
        $this->assertNotEmpty($this->genlytics->getDimensionReport(['today'], 'country'));

        $this->mockService->shouldReceive('getRealTime')->once()->andReturn($mockData);
        $this->assertNotEmpty($this->genlytics->getRealTime(['country'], ['activeUsers']));

        // Quota telemetry delegation
        $mockQuota = Mockery::mock(\zfhassaan\genlytics\Contracts\QuotaManagerInterface::class);
        $mockQuota->shouldReceive('getStatus')->once()->andReturn(new \zfhassaan\genlytics\DTOs\QuotaStatusDTO(['property_type' => 'standard']));
        $mockQuota->shouldReceive('getRemainingDailyTokens')->once()->andReturn(180000);
        $mockQuota->shouldReceive('shouldConsumeAllDailyQuota')->once()->andReturn(true);
        $mockQuota->shouldReceive('validateExecution')->once()->with(2, '127.0.0.1', 'u1')->andReturn(true);

        $this->mockService->shouldReceive('quota')->andReturn($mockQuota);

        $this->assertNotNull($this->genlytics->quotaStatus());
        $this->assertEquals(180000, $this->genlytics->remainingTokens());
        $this->assertTrue($this->genlytics->isAllQuotaConsumptionEnabled());
        $this->assertTrue($this->genlytics->validateQuota(2, '127.0.0.1', 'u1'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
