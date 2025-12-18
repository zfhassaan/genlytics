<?php

namespace zfhassaan\genlytics\Tests\Unit\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Mockery;
use zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface;
use zfhassaan\genlytics\Contracts\CacheManagerInterface;
use zfhassaan\genlytics\Contracts\DataTransformerInterface;
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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
