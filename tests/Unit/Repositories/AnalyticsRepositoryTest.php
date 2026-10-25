<?php

namespace zfhassaan\genlytics\Tests\Unit\Repositories;

use Google\Analytics\Data\V1beta\BatchRunReportsResponse;
use Google\Analytics\Data\V1beta\RunReportResponse;
use Google\Analytics\Data\V1beta\RunRealtimeReportResponse;
use Google\ApiCore\ApiException;
use Mockery;
use zfhassaan\genlytics\Contracts\QuotaManagerInterface;
use zfhassaan\genlytics\overrides\BetaAnalyticsDataClient;
use zfhassaan\genlytics\Repositories\AnalyticsRepository;
use zfhassaan\genlytics\Tests\TestCase;

class AnalyticsRepositoryTest extends TestCase
{
    protected AnalyticsRepository $repository;
    protected $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = Mockery::mock(BetaAnalyticsDataClient::class);
        $this->repository = new AnalyticsRepository($this->mockClient, 'properties/123456789');
    }

    public function test_can_run_report()
    {
        $dateRange = ['start_date' => '7daysAgo', 'end_date' => 'today'];
        $dimensions = [['name' => 'country']];
        $metrics = [['name' => 'activeUsers']];

        $mockResponse = $this->createMock(RunReportResponse::class);

        $this->mockClient
            ->shouldReceive('runReport')
            ->once()
            ->with(Mockery::on(function ($options) {
                return isset($options['property'])
                    && isset($options['dateRanges'])
                    && isset($options['dimensions'])
                    && isset($options['metrics']);
            }))
            ->andReturn($mockResponse);

        $result = $this->repository->runReport($dateRange, $dimensions, $metrics);

        $this->assertInstanceOf(RunReportResponse::class, $result);
    }

    public function test_can_run_realtime_report()
    {
        $dimensions = [['name' => 'country']];
        $metrics = [['name' => 'activeUsers']];

        $mockResponse = $this->createMock(RunRealtimeReportResponse::class);

        $this->mockClient
            ->shouldReceive('runRealtimeReport')
            ->once()
            ->with(Mockery::on(function ($options) {
                return isset($options['property'])
                    && isset($options['dimensions'])
                    && isset($options['metrics']);
            }))
            ->andReturn($mockResponse);

        $result = $this->repository->runRealtimeReport($dimensions, $metrics);

        $this->assertInstanceOf(RunRealtimeReportResponse::class, $result);
    }

    public function test_can_run_dimension_report()
    {
        $dateRange = ['start_date' => '7daysAgo', 'end_date' => 'today'];
        $dimension = 'country';

        $mockResponse = $this->createMock(RunReportResponse::class);

        $this->mockClient
            ->shouldReceive('runReport')
            ->once()
            ->with(Mockery::on(function ($options) {
                return isset($options['property'])
                    && isset($options['dateRanges'])
                    && isset($options['dimensions']);
            }))
            ->andReturn($mockResponse);

        $result = $this->repository->runDimensionReport($dateRange, $dimension);

        $this->assertInstanceOf(RunReportResponse::class, $result);
    }

    public function test_can_run_batch_reports()
    {
        $reports = [
            ['dimensions' => ['country'], 'metrics' => ['activeUsers']],
            ['dimensions' => ['city'], 'metrics' => ['sessions']]
        ];

        $mockResponse = $this->createMock(BatchRunReportsResponse::class);

        $this->mockClient
            ->shouldReceive('batchRunReports')
            ->once()
            ->with(Mockery::on(function ($options) {
                return isset($options['property']) && isset($options['requests']);
            }))
            ->andReturn($mockResponse);

        $result = $this->repository->batchRunReports($reports);

        $this->assertInstanceOf(BatchRunReportsResponse::class, $result);
    }

    public function test_handles_api_exception()
    {
        $dateRange = ['start_date' => '7daysAgo', 'end_date' => 'today'];
        $dimensions = [['name' => 'country']];
        $metrics = [['name' => 'activeUsers']];

        $this->mockClient
            ->shouldReceive('runReport')
            ->once()
            ->andThrow(new ApiException('API Error', 400));

        $this->expectException(ApiException::class);

        $this->repository->runReport($dateRange, $dimensions, $metrics);
    }

    public function test_handles_realtime_api_exception()
    {
        $this->mockClient
            ->shouldReceive('runRealtimeReport')
            ->once()
            ->andThrow(new ApiException('Realtime API Error', 400));

        $this->expectException(ApiException::class);

        $this->repository->runRealtimeReport([['name' => 'country']], [['name' => 'activeUsers']]);
    }

    public function test_can_get_property_id()
    {
        $this->assertEquals('properties/123456789', $this->repository->getPropertyId());
    }

    public function test_quota_manager_lifecycle_and_concurrency_slots()
    {
        $mockQuota = Mockery::mock(QuotaManagerInterface::class);
        $this->repository->setQuotaManager($mockQuota);
        $this->assertSame($mockQuota, $this->repository->getQuotaManager());

        $mockQuota->shouldReceive('acquireConcurrencySlot')->once()->andReturn(true);
        $mockQuota->shouldReceive('recordUsage')->once()->with(1);
        $mockQuota->shouldReceive('releaseConcurrencySlot')->once();

        $mockResponse = $this->createMock(RunReportResponse::class);
        $this->mockClient->shouldReceive('runReport')->once()->andReturn($mockResponse);

        $result = $this->repository->runReport(
            ['start_date' => '7daysAgo', 'end_date' => 'today'],
            [['name' => 'country']],
            [['name' => 'activeUsers']]
        );

        $this->assertInstanceOf(RunReportResponse::class, $result);
    }

    public function test_handles_multiple_dimensions_and_order_bys()
    {
        $dateRange = ['start_date' => '7daysAgo', 'end_date' => 'today'];
        $dimensions = [
            ['name' => 'country'],
            ['name' => 'city'],
        ];
        $metrics = [['name' => 'activeUsers']];
        $options = [
            'orderBys' => [
                ['metric' => ['metricName' => 'activeUsers'], 'desc' => true],
                ['dimension' => ['dimensionName' => 'country'], 'desc' => false]
            ]
        ];

        $mockResponse = $this->createMock(RunReportResponse::class);

        $this->mockClient
            ->shouldReceive('runReport')
            ->once()
            ->andReturn($mockResponse);

        $result = $this->repository->runReport($dateRange, $dimensions, $metrics, $options);

        $this->assertInstanceOf(RunReportResponse::class, $result);
    }

    public function test_batch_run_reports_handles_api_exception()
    {
        $mockQuota = Mockery::mock(QuotaManagerInterface::class);
        $mockQuota->shouldReceive('acquireConcurrencySlot')->once()->andReturn(true);
        $mockQuota->shouldReceive('releaseConcurrencySlot')->once();
        $this->repository->setQuotaManager($mockQuota);

        $this->mockClient
            ->shouldReceive('batchRunReports')
            ->once()
            ->andThrow(new ApiException('Batch failed', 400));

        $this->expectException(ApiException::class);

        $this->repository->batchRunReports([
            ['dimensions' => ['country'], 'metrics' => ['activeUsers']],
        ]);
    }

    public function test_dimension_report_with_array_and_exception()
    {
        $mockResponse = $this->createMock(RunReportResponse::class);
        $this->mockClient->shouldReceive('runReport')->once()->andReturn($mockResponse);

        $res = $this->repository->runDimensionReport(['start_date' => 'today', 'end_date' => 'today'], [['name' => 'city'], ['name' => 'country']]);
        $this->assertInstanceOf(RunReportResponse::class, $res);

        $this->mockClient->shouldReceive('runReport')->once()->andThrow(new ApiException('Dimension error', 400));
        $this->expectException(ApiException::class);
        $this->repository->runDimensionReport(['start_date' => 'today', 'end_date' => 'today'], 'city');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
