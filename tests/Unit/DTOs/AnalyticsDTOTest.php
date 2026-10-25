<?php

namespace zfhassaan\genlytics\Tests\Unit\DTOs;

use zfhassaan\genlytics\DTOs\AnalyticsRowDTO;
use zfhassaan\genlytics\DTOs\CountryReportDTO;
use zfhassaan\genlytics\DTOs\PageReportDTO;
use zfhassaan\genlytics\DTOs\TrafficSourceDTO;
use zfhassaan\genlytics\DTOs\UserEngagementDTO;
use zfhassaan\genlytics\Http\Resources\AnalyticsResource;
use zfhassaan\genlytics\Http\Resources\PageReportResource;
use zfhassaan\genlytics\Tests\TestCase;

class AnalyticsDTOTest extends TestCase
{
    public function test_can_instantiate_analytics_row_dto()
    {
        $dto = AnalyticsRowDTO::fromArray([
            'dimensions' => ['country' => 'United States'],
            'metrics'    => ['activeUsers' => '1420', 'bounceRate' => '0.35'],
        ]);

        $this->assertEquals('United States', $dto->getDimension('country'));
        $this->assertNull($dto->getDimension('unknown'));
        $this->assertEquals('United States', $dto->dimensions['country']);
        $this->assertEquals(1420, $dto->getIntMetric('activeUsers'));
        $this->assertEquals(0.35, $dto->getFloatMetric('bounceRate'));
        $this->assertEquals(1420, $dto->getMetric('activeUsers'));
        $this->assertArrayHasKey('dimensions', $dto->toArray());
        $this->assertArrayHasKey('metrics', $dto->jsonSerialize());
    }

    public function test_can_instantiate_page_report_dto_and_resource()
    {
        $row = [
            'dimensions' => ['pagePath' => '/pricing', 'pageTitle' => 'Pricing Page'],
            'metrics'    => ['screenPageViews' => '5420', 'activeUsers' => '1200', 'averageSessionDuration' => '125.5'],
        ];

        $dto = PageReportDTO::fromRow($row);

        $this->assertEquals('/pricing', $dto->path);
        $this->assertEquals('Pricing Page', $dto->title);
        $this->assertEquals(5420, $dto->views);
        $this->assertEquals(1200, $dto->activeUsers);
        $this->assertEquals('/pricing', $dto->toArray()['path']);
        $this->assertEquals(5420, $dto->jsonSerialize()['views']);

        $resource = new PageReportResource($dto);
        $array = $resource->toArray(request());

        $this->assertEquals('/pricing', $array['path']);
        $this->assertEquals(5420, $array['views']);
    }

    public function test_can_instantiate_country_report_dto()
    {
        $row = [
            'dimensions' => ['country' => 'Germany'],
            'metrics'    => ['activeUsers' => '850', 'sessions' => '1100'],
        ];

        $dto = CountryReportDTO::fromRow($row);

        $this->assertEquals('Germany', $dto->country);
        $this->assertEquals(850, $dto->activeUsers);
        $this->assertEquals(1100, $dto->sessions);
        $this->assertEquals('Germany', $dto->toArray()['country']);
        $this->assertEquals(850, $dto->jsonSerialize()['active_users']);
    }

    public function test_can_instantiate_traffic_source_dto()
    {
        $row = [
            'dimensions' => ['sessionSource' => 'google', 'sessionMedium' => 'organic'],
            'metrics'    => ['sessions' => '4200', 'activeUsers' => '3100', 'bounceRate' => '0.28'],
        ];

        $dto = TrafficSourceDTO::fromRow($row);

        $this->assertEquals('google', $dto->source);
        $this->assertEquals('organic', $dto->medium);
        $this->assertEquals(4200, $dto->sessions);
        $this->assertEquals('google', $dto->toArray()['source']);
        $this->assertEquals('organic', $dto->jsonSerialize()['medium']);
    }

    public function test_can_instantiate_user_engagement_dto()
    {
        $row = [
            'metrics' => [
                'activeUsers'            => '1000',
                'newUsers'               => '400',
                'engagedSessions'        => '750',
                'engagementRate'         => '0.75',
                'userEngagementDuration' => '180.2',
            ],
        ];

        $dto = UserEngagementDTO::fromRow($row);

        $this->assertEquals(1000, $dto->activeUsers);
        $this->assertEquals(400, $dto->newUsers);
        $this->assertEquals(750, $dto->engagedSessions);
        $this->assertEquals(0.75, $dto->engagementRate);
        $this->assertEquals(180.2, $dto->engagementDuration);
        $this->assertEquals(1000, $dto->toArray()['active_users']);
        $this->assertEquals(1000, $dto->jsonSerialize()['active_users']);
    }

    public function test_can_instantiate_product_performance_dto_and_resource()
    {
        $row = [
            'dimensions' => ['itemId' => 'SKU-001', 'itemName' => 'Pro Subscription', 'itemCategory' => 'SaaS'],
            'metrics'    => [
                'itemRevenue'       => '4990.00',
                'itemsPurchased'    => '50',
                'itemsViewed'       => '1000',
                'itemsAddedToCart'  => '200',
            ],
        ];

        $dto = \zfhassaan\genlytics\DTOs\ProductPerformanceDTO::fromRow($row);

        $this->assertEquals('SKU-001', $dto->id);
        $this->assertEquals('Pro Subscription', $dto->name);
        $this->assertEquals('SaaS', $dto->category);
        $this->assertEquals(4990.0, $dto->revenue);
        $this->assertEquals(50, $dto->itemsPurchased);
        $this->assertEquals(1000, $dto->itemsViewed);
        $this->assertEquals(200, $dto->itemsAddedToCart);
        $this->assertEquals(20.0, $dto->cartToViewRate);
        $this->assertEquals(5.0, $dto->purchaseToViewRate);
        $this->assertEquals('SKU-001', $dto->toArray()['id']);
        $this->assertEquals('Pro Subscription', $dto->jsonSerialize()['name']);

        $resource = new \zfhassaan\genlytics\Http\Resources\ProductPerformanceResource($dto);
        $array = $resource->toArray(request());

        $this->assertEquals('SKU-001', $array['id']);
        $this->assertEquals(4990.0, $array['revenue']);
    }

    public function test_can_instantiate_category_performance_dto()
    {
        $row = [
            'dimensions' => ['itemCategory' => 'Apparel'],
            'metrics'    => [
                'itemRevenue'      => '12500.50',
                'itemsPurchased'   => '310',
                'itemsViewed'      => '2500',
                'itemsAddedToCart' => '600',
            ],
        ];

        $dto = \zfhassaan\genlytics\DTOs\CategoryPerformanceDTO::fromRow($row);

        $this->assertEquals('Apparel', $dto->category);
        $this->assertEquals(12500.50, $dto->revenue);
        $this->assertEquals(310, $dto->itemsPurchased);
        $this->assertEquals(24.0, $dto->cartToViewRate);
        $this->assertEquals('Apparel', $dto->toArray()['category']);
        $this->assertEquals(12500.50, $dto->jsonSerialize()['revenue']);
    }

    public function test_can_instantiate_funnel_pipeline_dto()
    {
        $step1 = new \zfhassaan\genlytics\DTOs\FunnelStepDTO(
            step: 1,
            name: 'View Item',
            eventName: 'view_item',
            count: 1000,
            dropOffCount: 0,
            dropOffRate: 0.0,
            conversionRate: 100.0,
            overallConversionRate: 100.0
        );
        $this->assertEquals('View Item', $step1->toArray()['name']);
        $this->assertEquals(1000, $step1->jsonSerialize()['count']);

        $steps = collect([
            $step1,
            new \zfhassaan\genlytics\DTOs\FunnelStepDTO(
                step: 2,
                name: 'Add to Cart',
                eventName: 'add_to_cart',
                count: 350,
                dropOffCount: 650,
                dropOffRate: 65.0,
                conversionRate: 35.0,
                overallConversionRate: 35.0
            ),
            new \zfhassaan\genlytics\DTOs\FunnelStepDTO(
                step: 3,
                name: 'Purchase',
                eventName: 'purchase',
                count: 70,
                dropOffCount: 280,
                dropOffRate: 80.0,
                conversionRate: 20.0,
                overallConversionRate: 7.0
            ),
        ]);

        $pipeline = new \zfhassaan\genlytics\DTOs\FunnelPipelineDTO(
            name: 'E-Commerce Purchase Funnel',
            totalEntries: 1000,
            totalCompletions: 70,
            overallConversionRate: 7.0,
            steps: $steps
        );

        $this->assertEquals('E-Commerce Purchase Funnel', $pipeline->name);
        $this->assertEquals(1000, $pipeline->totalEntries);
        $this->assertEquals(70, $pipeline->totalCompletions);
        $this->assertEquals(7.0, $pipeline->overallConversionRate);
        $this->assertCount(3, $pipeline->steps);

        $array = $pipeline->toArray();
        $this->assertEquals('view_item', $array['steps'][0]['event_name']);
        $this->assertEquals(350, $array['steps'][1]['count']);
        $this->assertEquals(70, $array['steps'][2]['count']);
        $this->assertEquals(1000, $pipeline->jsonSerialize()['total_entries']);
    }
}
