<?php

namespace zfhassaan\genlytics\Tests\Unit\Repositories;

use Google\Analytics\Data\V1beta\RunReportResponse;
use Google\Analytics\Data\V1beta\RunRealtimeReportResponse;
use Google\ApiCore\ApiException;
use Mockery;
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

    public function test_can_get_property_id()
    {
        $propertyId = $this->repository->getPropertyId();
        
        $this->assertEquals('properties/123456789', $propertyId);
    }

    public function test_handles_multiple_dimensions()
    {
        $dateRange = ['start_date' => '7daysAgo', 'end_date' => 'today'];
        $dimensions = [
            ['name' => 'country'],
            ['name' => 'city'],
        ];
        $metrics = [['name' => 'activeUsers']];

        $mockResponse = $this->createMock(RunReportResponse::class);
        
        $this->mockClient
            ->shouldReceive('runReport')
            ->once()
            ->andReturn($mockResponse);

        $result = $this->repository->runReport($dateRange, $dimensions, $metrics);

        $this->assertInstanceOf(RunReportResponse::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

