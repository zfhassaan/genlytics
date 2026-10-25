<?php

namespace zfhassaan\genlytics\Tests\Unit\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery;
use stdClass;
use zfhassaan\genlytics\DTOs\AnalyticsRowDTO;
use zfhassaan\genlytics\DTOs\CategoryPerformanceDTO;
use zfhassaan\genlytics\DTOs\CountryReportDTO;
use zfhassaan\genlytics\DTOs\FunnelPipelineDTO;
use zfhassaan\genlytics\DTOs\PageReportDTO;
use zfhassaan\genlytics\DTOs\ProductPerformanceDTO;
use zfhassaan\genlytics\DTOs\QuotaStatusDTO;
use zfhassaan\genlytics\DTOs\TrafficSourceDTO;
use zfhassaan\genlytics\Genlytics;
use zfhassaan\genlytics\Http\Resources\AnalyticsResource;
use zfhassaan\genlytics\Http\Resources\AnalyticsResourceCollection;
use zfhassaan\genlytics\Http\Resources\CategoryPerformanceResource;
use zfhassaan\genlytics\Http\Resources\CountryReportResource;
use zfhassaan\genlytics\Http\Resources\FunnelPipelineResource;
use zfhassaan\genlytics\Http\Resources\PageReportResource;
use zfhassaan\genlytics\Http\Resources\ProductPerformanceResource;
use zfhassaan\genlytics\Http\Resources\QuotaStatusResource;
use zfhassaan\genlytics\Http\Resources\TrafficSourceResource;
use zfhassaan\genlytics\Query\AnalyticsQueryBuilder;
use zfhassaan\genlytics\Services\AnalyticsService;
use zfhassaan\genlytics\Tests\TestCase;

class ResourcesTest extends TestCase
{
    public function test_package_version_constant()
    {
        $this->assertEquals('1.1.0', Genlytics::VERSION);
        $this->assertEquals('1.1.0', Genlytics::version());
    }

    public function test_analytics_resource_contains_version_and_branches()
    {
        $request = Request::create('/test');

        // 1. Arrayable resource (AnalyticsRowDTO)
        $dto = new AnalyticsRowDTO(['country' => 'US'], ['activeUsers' => 100]);
        $resource = new AnalyticsResource($dto);
        $withData = $resource->with($request);
        $this->assertTrue($withData['success']);
        $this->assertEquals('1.1.0', $withData['version']);
        $this->assertEquals('1.1.0', $withData['meta']['version']);
        $this->assertArrayHasKey('timestamp', $withData['meta']);

        $resArray = $resource->toArray($request);
        $this->assertEquals('US', $resArray['dimensions']['country']);

        // 2. Non-arrayable AnalyticsRowDTO mock or raw array with dimensions & metrics
        $rawRow = [
            'dimensions' => ['city' => 'Chicago'],
            'metrics' => ['sessions' => 20],
        ];
        $resRaw = (new AnalyticsResource($rawRow))->toArray($request);
        $this->assertEquals('Chicago', $resRaw['dimensions']['city']);

        // 3. Plain associative array
        $plainArray = ['foo' => 'bar'];
        $resPlain = (new AnalyticsResource($plainArray))->toArray($request);
        $this->assertEquals(['foo' => 'bar'], $resPlain);

        // 4. Object
        $obj = new stdClass();
        $obj->key = 'val';
        $resObj = (new AnalyticsResource($obj))->toArray($request);
        $this->assertEquals(['key' => 'val'], $resObj);

        // 5. Generic Arrayable (Collection)
        $col = collect(['key' => 'col']);
        $resCol = (new AnalyticsResource($col))->toArray($request);
        $this->assertEquals(['key' => 'col'], $resCol);
    }

    public function test_analytics_resource_collection_contains_version()
    {
        $rows = collect([
            new AnalyticsRowDTO(['country' => 'US'], ['activeUsers' => 100]),
            new AnalyticsRowDTO(['country' => 'UK'], ['activeUsers' => 50]),
        ]);

        $collection = new AnalyticsResourceCollection($rows);
        $request = Request::create('/test');
        $withData = $collection->with($request);

        $this->assertTrue($withData['success']);
        $this->assertEquals('1.1.0', $withData['version']);
        $this->assertEquals(2, $withData['meta']['count']);
        $this->assertEquals('1.1.0', $withData['meta']['version']);

        $response = $collection->toResponse($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = $response->getData(true);
        $this->assertEquals('1.1.0', $data['version']);
        $this->assertCount(2, $data['data']);
    }

    public function test_page_report_resource_both_dto_and_raw_row()
    {
        $request = Request::create('/test');

        // From DTO
        $dto = new PageReportDTO('/home', 'Home Page', 500, 250, 45.5);
        $resource = new PageReportResource($dto);
        $data = $resource->toResponse($request)->getData(true);
        $this->assertEquals('1.1.0', $data['version']);
        $this->assertEquals('/home', $data['data']['path']);

        // From Raw Row
        $row = [
            'dimensions' => ['pagePath' => '/about', 'pageTitle' => 'About Us'],
            'metrics' => ['screenPageViews' => 120, 'activeUsers' => 80],
        ];
        $resourceRaw = new PageReportResource($row);
        $dataRaw = $resourceRaw->toResponse($request)->getData(true);
        $this->assertEquals('/about', $dataRaw['data']['path']);
    }

    public function test_country_report_resource_both_dto_and_raw_row()
    {
        $request = Request::create('/test');

        $dto = new CountryReportDTO('Germany', 300, 450);
        $resource = new CountryReportResource($dto);
        $data = $resource->toResponse($request)->getData(true);
        $this->assertEquals('1.1.0', $data['version']);
        $this->assertEquals('Germany', $data['data']['country']);

        $row = [
            'dimensions' => ['country' => 'France'],
            'metrics' => ['activeUsers' => 200, 'sessions' => 310],
        ];
        $resourceRaw = new CountryReportResource($row);
        $dataRaw = $resourceRaw->toResponse($request)->getData(true);
        $this->assertEquals('France', $dataRaw['data']['country']);
    }

    public function test_traffic_source_resource_both_dto_and_raw_row()
    {
        $request = Request::create('/test');

        $dto = new TrafficSourceDTO('google', 'organic', 1000, 850, 0.35);
        $resource = new TrafficSourceResource($dto);
        $data = $resource->toResponse($request)->getData(true);
        $this->assertEquals('1.1.0', $data['version']);
        $this->assertEquals('google', $data['data']['source']);

        $row = [
            'dimensions' => ['sessionSource' => 'direct', 'sessionMedium' => '(none)'],
            'metrics' => ['sessions' => 500, 'activeUsers' => 400],
        ];
        $resourceRaw = new TrafficSourceResource($row);
        $dataRaw = $resourceRaw->toResponse($request)->getData(true);
        $this->assertEquals('direct', $dataRaw['data']['source']);
    }

    public function test_product_performance_resource_both_dto_and_raw_row()
    {
        $request = Request::create('/test');

        $dto = new ProductPerformanceDTO('P1', 'Pro Laptop', 'Electronics', 1299.99, 5, 20, 8);
        $resource = new ProductPerformanceResource($dto);
        $data = $resource->toResponse($request)->getData(true);
        $this->assertEquals('1.1.0', $data['version']);
        $this->assertEquals('Pro Laptop', $data['data']['name']);

        $row = [
            'dimensions' => ['itemId' => 'P2', 'itemName' => 'Mouse'],
            'metrics' => ['itemRevenue' => 49.99, 'itemsPurchased' => 20],
        ];
        $resourceRaw = new ProductPerformanceResource($row);
        $dataRaw = $resourceRaw->toResponse($request)->getData(true);
        $this->assertEquals('Mouse', $dataRaw['data']['name']);
    }

    public function test_category_performance_resource_both_dto_and_raw_row()
    {
        $request = Request::create('/test');

        $dto = new CategoryPerformanceDTO('Apparel', 5400.0, 120, 450, 0.26);
        $resource = new CategoryPerformanceResource($dto);
        $data = $resource->toResponse($request)->getData(true);
        $this->assertEquals('1.1.0', $data['version']);
        $this->assertEquals('Apparel', $data['data']['category']);

        $row = [
            'dimensions' => ['itemCategory' => 'Footwear'],
            'metrics' => ['itemRevenue' => 3100.0, 'itemsPurchased' => 70],
        ];
        $resourceRaw = new CategoryPerformanceResource($row);
        $dataRaw = $resourceRaw->toResponse($request)->getData(true);
        $this->assertEquals('Footwear', $dataRaw['data']['category']);
    }

    public function test_funnel_pipeline_resource_both_dto_and_raw_row()
    {
        $request = Request::create('/test');

        $dto = new FunnelPipelineDTO('Purchase Funnel', 1000, 100, 10.0, collect([]));
        $resource = new FunnelPipelineResource($dto);
        $data = $resource->toResponse($request)->getData(true);
        $this->assertEquals('1.1.0', $data['version']);
        $this->assertEquals('Purchase Funnel', $data['data']['name']);

        $row = [
            'name' => 'Custom Funnel',
            'entries' => 500,
            'completions' => 50,
            'rate' => 10.0,
            'steps' => [],
        ];
        $resourceRaw = new FunnelPipelineResource($row);
        $dataRaw = $resourceRaw->toResponse($request)->getData(true);
        $this->assertEquals('Custom Funnel', $dataRaw['data']['name']);
    }

    public function test_quota_status_resource_contains_version()
    {
        $dto = new QuotaStatusDTO(['property_type' => 'standard', 'mode' => 'maximum_utilization']);
        $resource = new QuotaStatusResource($dto);

        $request = Request::create('/test');
        $response = $resource->toResponse($request);
        $data = $response->getData(true);

        $this->assertEquals('1.1.0', $data['version']);
        $this->assertEquals('standard', $data['data']['property_type']);
        $this->assertEquals('maximum_utilization', $data['data']['pacing']['mode']);

        // From array
        $raw = ['property_type' => '360', 'daily_tokens_limit' => 2000000];
        $resRaw = new QuotaStatusResource($raw);
        $dataRaw = $resRaw->toResponse($request)->getData(true);
        $this->assertEquals('360', $dataRaw['data']['property_type']);
    }

    public function test_query_builder_returns_api_resources_and_responses()
    {
        $mockService = Mockery::mock(AnalyticsService::class);
        $builder = new AnalyticsQueryBuilder($mockService);

        $mockData = [
            [
                'dimensions' => ['country' => 'US', 'pagePath' => '/docs', 'sessionSource' => 'google'],
                'metrics' => ['activeUsers' => '100', 'screenPageViews' => '250', 'sessions' => '90'],
            ],
        ];

        $mockService->shouldReceive('getReport')->times(4)->andReturn($mockData);

        // toPageResource
        $pageResources = $builder->toPageResource();
        $this->assertInstanceOf(\Illuminate\Http\Resources\Json\AnonymousResourceCollection::class, $pageResources);

        // toCountryResource
        $countryResources = $builder->toCountryResource();
        $this->assertInstanceOf(\Illuminate\Http\Resources\Json\AnonymousResourceCollection::class, $countryResources);

        // toTrafficSourceResource
        $trafficResources = $builder->toTrafficSourceResource();
        $this->assertInstanceOf(\Illuminate\Http\Resources\Json\AnonymousResourceCollection::class, $trafficResources);

        // toResponse
        $jsonResponse = $builder->toResponse();
        $this->assertInstanceOf(JsonResponse::class, $jsonResponse);
        $data = $jsonResponse->getData(true);
        $this->assertEquals('1.1.0', $data['version']);
    }

    public function test_genlytics_helper_resource_methods()
    {
        $mockService = Mockery::mock(AnalyticsService::class);
        $genlytics = new Genlytics($mockService);

        $mockData = [
            [
                'dimensions' => ['country' => 'Germany', 'pagePath' => '/pricing', 'sessionSource' => 'newsletter', 'itemId' => 'SKU1'],
                'metrics' => ['activeUsers' => 50, 'screenPageViews' => 100, 'sessions' => 40, 'itemRevenue' => 200.0],
            ],
        ];

        $mockService->shouldReceive('getReport')->times(4)->andReturn($mockData);

        $topPagesRes = $genlytics->topPagesResource(limit: 5);
        $this->assertEquals('1.1.0', $topPagesRes->toResponse(Request::create('/test'))->getData(true)['version']);

        $topCountriesRes = $genlytics->topCountriesResource(limit: 5);
        $this->assertEquals('1.1.0', $topCountriesRes->toResponse(Request::create('/test'))->getData(true)['version']);

        $trafficRes = $genlytics->trafficSourcesResource(limit: 5);
        $this->assertEquals('1.1.0', $trafficRes->toResponse(Request::create('/test'))->getData(true)['version']);

        $productsRes = $genlytics->topProductsResource(limit: 5);
        $this->assertEquals('1.1.0', $productsRes->toResponse(Request::create('/test'))->getData(true)['version']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
