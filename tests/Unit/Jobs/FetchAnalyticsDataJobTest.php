<?php

namespace zfhassaan\genlytics\Tests\Unit\Jobs;

use Illuminate\Support\Facades\Event;
use Mockery;
use zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface;
use zfhassaan\genlytics\Contracts\CacheManagerInterface;
use zfhassaan\genlytics\Contracts\DataTransformerInterface;
use zfhassaan\genlytics\Events\AnalyticsCacheUpdated;
use zfhassaan\genlytics\Events\AnalyticsDataFetched;
use zfhassaan\genlytics\Events\AnalyticsQueryFailed;
use zfhassaan\genlytics\Jobs\FetchAnalyticsDataJob;
use zfhassaan\genlytics\Tests\TestCase;

class FetchAnalyticsDataJobTest extends TestCase
{
    protected $mockRepository;
    protected $mockCacheManager;
    protected $mockTransformer;

    protected function setUp(): void
    {
        parent::setUp();
        
        Event::fake();
        
        $this->mockRepository = Mockery::mock(AnalyticsRepositoryInterface::class);
        $this->mockCacheManager = Mockery::mock(CacheManagerInterface::class);
        $this->mockTransformer = Mockery::mock(DataTransformerInterface::class);
    }

    public function test_job_fetches_and_caches_report_data()
    {
        $job = new FetchAnalyticsDataJob('report', [
            'dateRange' => ['start_date' => '7daysAgo', 'end_date' => 'today'],
            'dimensions' => [['name' => 'country']],
            'metrics' => [['name' => 'activeUsers']],
        ], 'cache:key');

        $mockResponse = $this->createMock(\Google\Analytics\Data\V1beta\RunReportResponse::class);
        $transformedData = [['dimensions' => ['country' => 'US'], 'metrics' => ['activeUsers' => '100']]];

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
            ->with('cache:key', $transformedData)
            ->andReturn(true);

        $job->handle($this->mockRepository, $this->mockCacheManager, $this->mockTransformer);

        Event::assertDispatched(AnalyticsCacheUpdated::class);
        Event::assertDispatched(AnalyticsDataFetched::class);
    }

    public function test_job_handles_realtime_reports()
    {
        $job = new FetchAnalyticsDataJob('realtime', [
            'dimensions' => [['name' => 'country']],
            'metrics' => [['name' => 'activeUsers']],
        ], 'cache:key');

        $mockResponse = $this->createMock(\Google\Analytics\Data\V1beta\RunRealtimeReportResponse::class);
        $transformedData = [['dimensions' => ['country' => 'US'], 'metrics' => ['activeUsers' => '50']]];

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
            ->andReturn(true);

        $job->handle($this->mockRepository, $this->mockCacheManager, $this->mockTransformer);

        Event::assertDispatched(AnalyticsCacheUpdated::class);
    }

    public function test_job_fires_failure_event_on_error()
    {
        $job = new FetchAnalyticsDataJob('report', ['test' => 'data'], 'cache:key');

        $this->mockRepository
            ->shouldReceive('runReport')
            ->once()
            ->andThrow(new \Exception('API Error'));

        try {
            $job->handle($this->mockRepository, $this->mockCacheManager, $this->mockTransformer);
        } catch (\Exception $e) {
            // Expected
        }

        Event::assertDispatched(AnalyticsQueryFailed::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

