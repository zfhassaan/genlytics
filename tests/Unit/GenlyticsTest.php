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
        $dimension = ['name' => 'country']; // Single dimension
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

        $this->assertInstanceOf(AnalyticsService::class, $service);
        $this->assertSame($this->mockService, $service);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
