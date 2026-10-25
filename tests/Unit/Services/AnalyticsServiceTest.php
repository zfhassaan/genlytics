<?php

namespace zfhassaan\genlytics\Tests\Unit\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Mockery;
use zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface;
use zfhassaan\genlytics\Contracts\CacheManagerInterface;
use zfhassaan\genlytics\Contracts\DataTransformerInterface;
use zfhassaan\genlytics\Contracts\QuotaManagerInterface;
use zfhassaan\genlytics\Events\AnalyticsDataFetched;
use zfhassaan\genlytics\Events\AnalyticsDataRequested;
use zfhassaan\genlytics\Events\AnalyticsQueryFailed;
use zfhassaan\genlytics\Jobs\FetchAnalyticsDataJob;
use zfhassaan\genlytics\Services\AnalyticsService;
use zfhassaan\genlytics\Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    protected $mockRepository;
    protected $mockCacheManager;
    protected $mockTransformer;
    protected AnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->mockRepository = Mockery::mock(AnalyticsRepositoryInterface::class);
        $this->mockCacheManager = Mockery::mock(CacheManagerInterface::class);
        $this->mockTransformer = Mockery::mock(DataTransformerInterface::class);

        $this->service = new AnalyticsService(
            $this->mockRepository,
            $this->mockCacheManager,
            $this->mockTransformer,
            false, // Disable background jobs for testing
            true,  // Enable cache
            true   // Enable real-time updates
        );
    }

    public function test_can_run_reports()
    {
        $dateRange = ['start_date' => '7daysAgo', 'end_date' => 'today'];
        $dimensions = [['name' => 'country']];
        $metrics = [['name' => 'activeUsers']];

        $mockResponse = $this->createMock(\Google\Analytics\Data\V1beta\RunReportResponse::class);
        $transformedData = [
            ['dimensions' => ['country' => 'US'], 'metrics' => ['activeUsers' => '100']]
        ];

        $this->mockCacheManager
            ->shouldReceive('generateKey')
            ->once()
            ->andReturn('report:abc123');

        $this->mockCacheManager
            ->shouldReceive('has')
            ->once()
            ->with('report:abc123')
            ->andReturn(false);

        $this->mockRepository
            ->shouldReceive('runReport')
            ->once()
            ->andReturn($mockResponse);

        $this->mockTransformer
            ->shouldReceive('transformReport')
            ->once()
            ->with($mockResponse)
            ->andReturn($transformedData);

        $this->mockCacheManager
            ->shouldReceive('put')
            ->once()
            ->andReturn(true);

        $this->mockTransformer
            ->shouldReceive('toJsonFormat')
            ->once()
            ->andReturn(['data' => $transformedData, 'metadata' => []]);

        $result = $this->service->runReports($dateRange, $dimensions, $metrics);

        $this->assertInstanceOf(JsonResponse::class, $result);
        Event::assertDispatched(AnalyticsDataRequested::class);
        Event::assertDispatched(AnalyticsDataFetched::class);
    }

    public function test_returns_cached_data_when_available()
    {
        $dateRange = ['start_date' => '7daysAgo', 'end_date' => 'today'];
        $dimensions = [['name' => 'country']];
        $metrics = [['name' => 'activeUsers']];

        $cachedData = [
            ['dimensions' => ['country' => 'US'], 'metrics' => ['activeUsers' => '100']]
        ];

        $this->mockCacheManager
            ->shouldReceive('generateKey')
            ->once()
            ->andReturn('report:abc123');

        $this->mockCacheManager
            ->shouldReceive('has')
            ->once()
            ->with('report:abc123')
            ->andReturn(true);

        $this->mockCacheManager
            ->shouldReceive('get')
            ->once()
            ->with('report:abc123')
            ->andReturn($cachedData);

        $this->mockTransformer
            ->shouldReceive('toJsonFormat')
            ->once()
            ->andReturn(['data' => $cachedData, 'metadata' => []]);

        $result = $this->service->runReports($dateRange, $dimensions, $metrics);

        $this->assertInstanceOf(JsonResponse::class, $result);
        Event::assertDispatched(AnalyticsDataFetched::class, function ($event) {
            return $event->fromCache === true;
        });
    }

    public function test_can_run_realtime_reports()
    {
        $dimensions = [['name' => 'country']];
        $metrics = [['name' => 'activeUsers']];

        $mockResponse = $this->createMock(\Google\Analytics\Data\V1beta\RunRealtimeReportResponse::class);
        $transformedData = [
            ['dimensions' => ['country' => 'US'], 'metrics' => ['activeUsers' => '50']]
        ];

        $this->mockCacheManager
            ->shouldReceive('generateKey')
            ->once()
            ->andReturn('realtime:xyz789');

        $this->mockCacheManager
            ->shouldReceive('has')
            ->once()
            ->andReturn(false);

        $this->mockRepository
            ->shouldReceive('runRealtimeReport')
            ->once()
            ->andReturn($mockResponse);

        $this->mockTransformer
            ->shouldReceive('transformRealtimeReport')
            ->once()
            ->with($mockResponse)
            ->andReturn($transformedData);

        $this->mockCacheManager
            ->shouldReceive('put')
            ->once()
            ->with('realtime:xyz789', $transformedData, 30)
            ->andReturn(true);

        $this->mockTransformer
            ->shouldReceive('toJsonFormat')
            ->once()
            ->andReturn(['data' => $transformedData, 'metadata' => []]);

        $result = $this->service->runRealTime($dimensions, $metrics);

        $this->assertInstanceOf(JsonResponse::class, $result);
        Event::assertDispatched(AnalyticsDataRequested::class);
    }

    public function test_handles_errors_gracefully()
    {
        $dateRange = ['start_date' => '7daysAgo', 'end_date' => 'today'];
        $dimensions = [['name' => 'country']];
        $metrics = [['name' => 'activeUsers']];

        $this->mockCacheManager
            ->shouldReceive('generateKey')
            ->once()
            ->andReturn('report:abc123');

        $this->mockCacheManager
            ->shouldReceive('has')
            ->once()
            ->andReturn(false);

        $this->mockRepository
            ->shouldReceive('runReport')
            ->once()
            ->andThrow(new \Exception('API Error'));

        $result = $this->service->runReports($dateRange, $dimensions, $metrics);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
        Event::assertDispatched(AnalyticsQueryFailed::class);
    }

    public function test_force_refresh_bypasses_cache()
    {
        $dateRange = ['start_date' => '7daysAgo', 'end_date' => 'today'];
        $dimensions = [['name' => 'country']];
        $metrics = [['name' => 'activeUsers']];

        $mockResponse = $this->createMock(\Google\Analytics\Data\V1beta\RunReportResponse::class);
        $transformedData = [
            ['dimensions' => ['country' => 'US'], 'metrics' => ['activeUsers' => '100']]
        ];

        $this->mockCacheManager
            ->shouldReceive('generateKey')
            ->once()
            ->andReturn('report:abc123');

        $this->mockRepository
            ->shouldReceive('runReport')
            ->once()
            ->andReturn($mockResponse);

        $this->mockTransformer
            ->shouldReceive('transformReport')
            ->once()
            ->andReturn($transformedData);

        $this->mockCacheManager
            ->shouldReceive('put')
            ->once()
            ->andReturn(true);

        $this->mockTransformer
            ->shouldReceive('toJsonFormat')
            ->once()
            ->andReturn(['data' => $transformedData, 'metadata' => []]);

        $result = $this->service->runReports($dateRange, $dimensions, $metrics, [], true);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->mockCacheManager->shouldNotHaveReceived('has');
    }

    public function test_can_run_dimension_report()
    {
        $dateRange = ['start_date' => '7daysAgo', 'end_date' => 'today'];
        $dimension = 'country';

        $mockResponse = $this->createMock(\Google\Analytics\Data\V1beta\RunReportResponse::class);
        $transformedData = [
            ['dimensions' => ['country' => 'US']]
        ];

        $this->mockCacheManager
            ->shouldReceive('generateKey')
            ->once()
            ->andReturn('dimension:abc123');

        $this->mockCacheManager
            ->shouldReceive('has')
            ->once()
            ->with('dimension:abc123')
            ->andReturn(false);

        $this->mockRepository
            ->shouldReceive('runDimensionReport')
            ->once()
            ->andReturn($mockResponse);

        $this->mockTransformer
            ->shouldReceive('transformReport')
            ->once()
            ->with($mockResponse)
            ->andReturn($transformedData);

        $this->mockCacheManager
            ->shouldReceive('put')
            ->once()
            ->andReturn(true);

        $this->mockTransformer
            ->shouldReceive('toJsonFormat')
            ->once()
            ->andReturn(['data' => $transformedData, 'metadata' => []]);

        $result = $this->service->runDimensionReport($dateRange, $dimension);

        $this->assertInstanceOf(JsonResponse::class, $result);
        Event::assertDispatched(AnalyticsDataRequested::class);
        Event::assertDispatched(AnalyticsDataFetched::class);
    }

    public function test_can_get_dimension_report_from_cache()
    {
        $dateRange = ['start_date' => '7daysAgo', 'end_date' => 'today'];
        $cachedData = [['dimensions' => ['country' => 'US']]];

        $this->mockCacheManager
            ->shouldReceive('generateKey')
            ->once()
            ->andReturn('dimension:abc123');

        $this->mockCacheManager
            ->shouldReceive('has')
            ->once()
            ->with('dimension:abc123')
            ->andReturn(true);

        $this->mockCacheManager
            ->shouldReceive('get')
            ->once()
            ->with('dimension:abc123')
            ->andReturn($cachedData);

        $result = $this->service->getDimensionReport($dateRange, 'country');

        $this->assertEquals($cachedData, $result);
        $this->mockRepository->shouldNotHaveReceived('runDimensionReport');
    }

    public function test_can_run_batch_reports()
    {
        $reports = [
            ['dimensions' => ['country'], 'metrics' => ['activeUsers']],
            ['dimensions' => ['city'], 'metrics' => ['activeUsers']],
        ];

        $mockResponse = $this->createMock(\Google\Analytics\Data\V1beta\BatchRunReportsResponse::class);
        $transformedData = [
            ['report_1' => ['data' => []]],
            ['report_2' => ['data' => []]],
        ];

        $this->mockCacheManager
            ->shouldReceive('generateKey')
            ->once()
            ->andReturn('batch:abc123');

        $this->mockCacheManager
            ->shouldReceive('has')
            ->once()
            ->with('batch:abc123')
            ->andReturn(false);

        $this->mockRepository
            ->shouldReceive('batchRunReports')
            ->once()
            ->with($reports)
            ->andReturn($mockResponse);

        $this->mockTransformer
            ->shouldReceive('transformBatchReport')
            ->once()
            ->with($mockResponse)
            ->andReturn($transformedData);

        $this->mockCacheManager
            ->shouldReceive('put')
            ->once()
            ->andReturn(true);

        $this->mockTransformer
            ->shouldReceive('toJsonFormat')
            ->once()
            ->andReturn(['data' => $transformedData, 'metadata' => []]);

        $result = $this->service->batchRunReports($reports);

        $this->assertInstanceOf(JsonResponse::class, $result);
        Event::assertDispatched(AnalyticsDataRequested::class);
        Event::assertDispatched(AnalyticsDataFetched::class);
    }

    public function test_can_get_batch_reports_from_cache()
    {
        $reports = [['dimensions' => ['country'], 'metrics' => ['activeUsers']]];
        $cachedData = [['report_1' => ['data' => []]]];

        $this->mockCacheManager
            ->shouldReceive('generateKey')
            ->once()
            ->andReturn('batch:abc123');

        $this->mockCacheManager
            ->shouldReceive('has')
            ->once()
            ->with('batch:abc123')
            ->andReturn(true);

        $this->mockCacheManager
            ->shouldReceive('get')
            ->once()
            ->with('batch:abc123')
            ->andReturn($cachedData);

        $result = $this->service->getBatchReports($reports);

        $this->assertEquals($cachedData, $result);
        $this->mockRepository->shouldNotHaveReceived('batchRunReports');
    }

    public function test_quota_manager_accessors_and_strict_exhaustion()
    {
        $mockQuota = Mockery::mock(\zfhassaan\genlytics\Contracts\QuotaManagerInterface::class);
        $this->service->setQuotaManager($mockQuota);

        $this->assertSame($mockQuota, $this->service->getQuotaManager());
        $this->assertSame($mockQuota, $this->service->quota());

        $mockQuota->shouldReceive('canExecute')->once()->andReturn(false);
        $mockQuota->shouldReceive('getSecondsUntilDailyReset')->once()->andReturn(1200);

        $this->mockCacheManager->shouldReceive('generateKey')->once()->andReturn('report:strict');
        $this->mockCacheManager->shouldReceive('has')->once()->with('report:strict')->andReturn(false);

        config(['analytics.quota.exhaustion_strategy' => 'strict']);

        $this->expectException(\zfhassaan\genlytics\Exceptions\QuotaExceededException::class);
        $this->service->getReport(['start_date' => '7daysAgo', 'end_date' => 'today'], [], []);
    }

    public function test_stale_cache_served_when_approaching_warning_threshold()
    {
        $mockQuota = Mockery::mock(\zfhassaan\genlytics\Contracts\QuotaManagerInterface::class);
        $this->service->setQuotaManager($mockQuota);

        $mockQuota->shouldReceive('canExecute')->once()->andReturn(true);
        $mockQuota->shouldReceive('shouldUseStaleCache')->once()->andReturn(true);

        $cachedData = [['dimensions' => ['country' => 'US']]];

        $this->mockCacheManager->shouldReceive('generateKey')->once()->andReturn('report:stale');
        $this->mockCacheManager->shouldReceive('has')->once()->with('report:stale')->andReturn(true);
        $this->mockCacheManager->shouldReceive('get')->once()->with('report:stale')->andReturn($cachedData);

        $result = $this->service->getReport(['start_date' => '7daysAgo', 'end_date' => 'today'], [], [], [], true);

        $this->assertEquals($cachedData, $result);
        $this->mockRepository->shouldNotHaveReceived('runReport');
    }

    public function test_set_and_get_cache_ttl()
    {
        $this->mockCacheManager->shouldReceive('setLifetime')->once()->with(7200, 'report');
        $this->mockCacheManager->shouldReceive('getLifetime')->once()->with('report')->andReturn(7200);

        $this->service->setCacheTtl(7200, 'report');
        $this->assertEquals(7200, $this->service->getCacheTtl('report'));

        $this->mockCacheManager->shouldReceive('setLifetime')->once()->with(45, 'realtime');
        $this->service->setCacheTtl(45, 'realtime');
        $this->assertEquals(45, $this->service->getCacheTtl('realtime'));
    }

    public function test_handles_errors_gracefully_across_methods()
    {
        $this->mockCacheManager->shouldReceive('generateKey')->once()->andReturn('realtime:err');
        $this->mockCacheManager->shouldReceive('has')->once()->andReturn(false);
        $this->mockRepository->shouldReceive('runRealtimeReport')->once()->andThrow(new \Exception('Realtime API error'));
        $this->mockTransformer->shouldReceive('toJsonFormat')->never();
        $respRealtime = $this->service->runRealTime([], []);
        $this->assertEquals(400, $respRealtime->getStatusCode());

        $this->mockCacheManager->shouldReceive('generateKey')->once()->andReturn('dimension:err');
        $this->mockCacheManager->shouldReceive('has')->once()->andReturn(false);
        $this->mockRepository->shouldReceive('runDimensionReport')->once()->andThrow(new \Exception('Dimension API error'));
        $respDim = $this->service->runDimensionReport(['start_date' => '7daysAgo', 'end_date' => 'today'], 'country');
        $this->assertEquals(400, $respDim->getStatusCode());

        $this->mockCacheManager->shouldReceive('generateKey')->once()->andReturn('batch:err');
        $this->mockCacheManager->shouldReceive('has')->once()->andReturn(false);
        $this->mockRepository->shouldReceive('batchRunReports')->once()->andThrow(new \Exception('Batch API error'));
        $respBatch = $this->service->batchRunReports([]);
        $this->assertEquals(400, $respBatch->getStatusCode());
    }

    public function test_service_getter_and_setter_methods()
    {
        $mockQuota = Mockery::mock(QuotaManagerInterface::class);
        $this->service->setQuotaManager($mockQuota);
        $this->assertSame($mockQuota, $this->service->getQuotaManager());
        $this->assertSame($mockQuota, $this->service->quota());

        $this->assertSame($this->mockCacheManager, $this->service->getCacheManager());

        $this->mockCacheManager->shouldReceive('getLifetime')->once()->with('report')->andReturn(3600);
        $this->assertEquals(3600, $this->service->getCacheTtl('report'));

        $this->mockCacheManager->shouldReceive('setLifetime')->once()->with(45, 'realtime');
        $this->service->setCacheTtl(45, 'realtime');
        $this->assertEquals(45, $this->service->getCacheTtl('realtime'));

        // getBatchReports test
        $this->mockCacheManager->shouldReceive('generateKey')->once()->andReturn('batch:key');
        $this->mockCacheManager->shouldReceive('has')->once()->andReturn(false);
        $mockBatchResponse = $this->createMock(\Google\Analytics\Data\V1beta\BatchRunReportsResponse::class);
        $this->mockRepository->shouldReceive('batchRunReports')->once()->andReturn($mockBatchResponse);
        $this->mockTransformer->shouldReceive('transformBatchReport')->once()->andReturn([['country' => 'US']]);
        $this->mockCacheManager->shouldReceive('put')->once();

        $batchResult = $this->service->getBatchReports([]);
        $this->assertEquals([['country' => 'US']], $batchResult);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
