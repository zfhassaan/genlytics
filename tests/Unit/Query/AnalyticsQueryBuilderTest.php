<?php

namespace zfhassaan\genlytics\Tests\Unit\Query;

use DateInterval;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Mockery;
use zfhassaan\genlytics\Contracts\QuotaManagerInterface;
use zfhassaan\genlytics\Query\AnalyticsQueryBuilder;
use zfhassaan\genlytics\Services\AnalyticsService;
use zfhassaan\genlytics\Tests\TestCase;

class AnalyticsQueryBuilderTest extends TestCase
{
    protected $mockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockService = Mockery::mock(AnalyticsService::class);
    }

    public function test_can_build_and_execute_fluent_query()
    {
        $expectedResult = [
            ['dimensions' => ['country' => 'US'], 'metrics' => ['activeUsers' => 100]],
        ];

        $this->mockService
            ->shouldReceive('getReport')
            ->once()
            ->with(
                ['start_date' => '7daysAgo', 'end_date' => 'today'],
                [['name' => 'country']],
                [['name' => 'activeUsers']],
                ['limit' => 5],
                false,
                null
            )
            ->andReturn($expectedResult);

        $builder = new AnalyticsQueryBuilder($this->mockService);
        $result = $builder
            ->last7Days()
            ->dimension('country')
            ->metric('activeUsers')
            ->limit(5)
            ->get();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertEquals('US', $result->first()['dimensions']['country']);
    }

    public function test_can_build_realtime_fluent_query()
    {
        $expectedResult = [
            ['dimensions' => ['country' => 'US'], 'metrics' => ['activeUsers' => 50]],
        ];

        $this->mockService
            ->shouldReceive('getRealTime')
            ->once()
            ->with(
                [['name' => 'country']],
                [['name' => 'activeUsers']],
                [],
                false,
                null
            )
            ->andReturn($expectedResult);

        $builder = new AnalyticsQueryBuilder($this->mockService);
        $result = $builder
            ->realtime()
            ->dimension('country')
            ->metric('activeUsers')
            ->get();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    public function test_can_configure_custom_ttl_via_remember_and_cache_for()
    {
        $expectedResult = [
            ['dimensions' => ['country' => 'CA'], 'metrics' => ['activeUsers' => 42]],
        ];

        $this->mockService
            ->shouldReceive('getReport')
            ->once()
            ->with(
                ['start_date' => '30daysAgo', 'end_date' => 'today'],
                [['name' => 'country']],
                [['name' => 'activeUsers']],
                [],
                false,
                7200
            )
            ->andReturn($expectedResult);

        $builder = new AnalyticsQueryBuilder($this->mockService);
        $result = $builder
            ->last30Days()
            ->dimension('country')
            ->metric('activeUsers')
            ->remember(7200)
            ->get();

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_can_configure_ttl_with_datetime_and_dateinterval()
    {
        $expectedResult = [
            ['dimensions' => ['country' => 'UK'], 'metrics' => ['activeUsers' => 12]],
        ];

        $this->mockService
            ->shouldReceive('getReport')
            ->twice()
            ->andReturn($expectedResult);

        $builder = new AnalyticsQueryBuilder($this->mockService);

        // DateInterval
        $builder->cacheFor(new DateInterval('PT1H'))->get();

        // DateTimeInterface
        $builder->ttl(new DateTimeImmutable('+2 hours'))->get();

        $this->assertTrue(true);
    }

    public function test_dont_cache_sets_force_refresh_to_true()
    {
        $expectedResult = [
            ['dimensions' => ['country' => 'FR'], 'metrics' => ['activeUsers' => 8]],
        ];

        $this->mockService
            ->shouldReceive('getReport')
            ->once()
            ->with(
                ['start_date' => '7daysAgo', 'end_date' => 'today'],
                [],
                [],
                [],
                true,
                null
            )
            ->andReturn($expectedResult);

        $builder = new AnalyticsQueryBuilder($this->mockService);
        $builder->last7Days()->dontCache()->get();
        $this->assertTrue(true);
    }

    public function test_can_chain_consume_all_quota_and_max_quota()
    {
        $mockQuota = Mockery::mock(QuotaManagerInterface::class);
        $mockQuota->shouldReceive('enableMaximumUtilization')->once();
        $mockQuota->shouldReceive('enableAllQuotaConsumption')->once();

        $this->mockService->shouldReceive('quota')->zeroOrMoreTimes()->andReturn($mockQuota);
        $this->mockService->shouldReceive('getReport')->once()->andReturn([]);

        $builder = new AnalyticsQueryBuilder($this->mockService);
        $builder->withMaxQuota()->withAllQuota()->get();

        $this->assertTrue(true);
    }

    public function test_query_builder_predefined_date_ranges_and_ordering()
    {
        $mockData = [
            ['dimensions' => ['country' => 'US', 'city' => 'NY'], 'metrics' => ['activeUsers' => 10]],
        ];

        $this->mockService->shouldReceive('getReport')->times(5)->andReturn($mockData);

        $builder = new AnalyticsQueryBuilder($this->mockService);

        // today()
        $builder->today()->get();

        // yesterday()
        $builder->yesterday()->get();

        // last14Days()
        $builder->last14Days()->get();

        // last90Days()
        $builder->last90Days()->get();

        // fresh() and orderBy() and offset()
        $builder->fresh()->offset(10)->orderBy('activeUsers', 'ASC')->noCache()->get();

        $this->assertTrue(true);
    }

    public function test_query_builder_first_and_count()
    {
        $expectedResult = [
            ['dimensions' => ['country' => 'US'], 'metrics' => ['activeUsers' => 100]],
            ['dimensions' => ['country' => 'UK'], 'metrics' => ['activeUsers' => 50]],
        ];

        $this->mockService
            ->shouldReceive('getReport')
            ->twice()
            ->andReturn($expectedResult);

        $builder = new AnalyticsQueryBuilder($this->mockService);

        $this->assertEquals(2, $builder->count());

        $first = $builder->first();
        $this->assertEquals('US', $first['dimensions']['country']);
    }

    public function test_query_builder_typed_dto_and_resource_conversions()
    {
        $mockData = [
            [
                'dimensions' => ['country' => 'Germany', 'itemCategory' => 'Tech', 'sessionSource' => 'google', 'itemId' => 'P1', 'itemName' => 'Phone', 'pagePath' => '/docs'],
                'metrics' => ['activeUsers' => 10, 'sessions' => 20, 'itemRevenue' => 500.0, 'itemsPurchased' => 2, 'screenPageViews' => 100],
            ],
        ];

        $this->mockService->shouldReceive('getReport')->times(7)->andReturn($mockData);

        $builder = new AnalyticsQueryBuilder($this->mockService);

        // toDto
        $this->assertNotEmpty($builder->toDto());

        // toCountryDto
        $this->assertNotEmpty($builder->toCountryDto());

        // toTrafficSourceDto
        $this->assertNotEmpty($builder->toTrafficSourceDto());

        // toProductDto
        $this->assertNotEmpty($builder->toProductDto());

        // toCategoryDto
        $this->assertNotEmpty($builder->toCategoryDto());

        // toProductResource
        $this->assertNotEmpty($builder->toProductResource());

        // toCategoryResource
        $this->assertNotEmpty($builder->toCategoryResource());
    }

    public function test_query_builder_typed_resources_and_json_response()
    {
        $mockData = [
            [
                'dimensions' => ['country' => 'Germany', 'itemCategory' => 'Tech', 'sessionSource' => 'google'],
                'metrics' => ['activeUsers' => 10, 'sessions' => 20, 'itemRevenue' => 500.0, 'itemsPurchased' => 2],
            ],
        ];

        $this->mockService->shouldReceive('getReport')->times(4)->andReturn($mockData);

        $builder = new AnalyticsQueryBuilder($this->mockService);

        // Country resource
        $countryRes = $builder->toCountryResource();
        $response = $countryRes->toResponse(\Illuminate\Http\Request::create('/test'));
        $data = $response->getData(true);
        $this->assertEquals('1.0.5', $data['version']);
        $this->assertEquals('Germany', $data['data'][0]['country']);

        // Traffic source resource
        $trafficRes = $builder->toTrafficSourceResource();
        $this->assertEquals('1.0.5', $trafficRes->toResponse(\Illuminate\Http\Request::create('/test'))->getData(true)['version']);

        // Category resource
        $categoryRes = $builder->toCategoryResource();
        $this->assertEquals('1.0.5', $categoryRes->toResponse(\Illuminate\Http\Request::create('/test'))->getData(true)['version']);

        // Direct toResponse()
        $jsonResp = $builder->toResponse();
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $jsonResp);
        $this->assertEquals('1.0.5', $jsonResp->getData(true)['version']);
    }

    public function test_query_builder_alias_methods_and_to_json()
    {
        $mockQuota = Mockery::mock(\zfhassaan\genlytics\Contracts\QuotaManagerInterface::class);
        $mockQuota->shouldReceive('enableMaximumUtilization')->once()->andReturnSelf();
        $mockQuota->shouldReceive('enableAllQuotaConsumption')->once()->andReturnSelf();
        $this->mockService->shouldReceive('quota')->andReturn($mockQuota);

        $mockData = [['country' => 'US']];
        $this->mockService->shouldReceive('runReports')->once()->andReturn(response()->json($mockData));
        $this->mockService->shouldReceive('runRealTime')->once()->andReturn(response()->json($mockData));

        $builder = (new AnalyticsQueryBuilder($this->mockService))
            ->withMaxQuota()
            ->withAllQuota()
            ->cacheFor(600)
            ->ttl(300)
            ->noCache();

        $responseStandard = $builder->toJson();
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $responseStandard);

        $realtimeBuilder = (new AnalyticsQueryBuilder($this->mockService))->realtime();
        $responseRealtime = $realtimeBuilder->toJson();
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $responseRealtime);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
