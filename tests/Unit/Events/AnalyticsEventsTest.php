<?php

namespace zfhassaan\genlytics\Tests\Unit\Events;

use Exception;
use zfhassaan\genlytics\Events\AnalyticsCacheUpdated;
use zfhassaan\genlytics\Events\AnalyticsDataFetched;
use zfhassaan\genlytics\Events\AnalyticsDataRequested;
use zfhassaan\genlytics\Events\AnalyticsQueryFailed;
use zfhassaan\genlytics\Tests\TestCase;

class AnalyticsEventsTest extends TestCase
{
    public function test_analytics_data_requested_event()
    {
        $event = new AnalyticsDataRequested('report', ['test' => 'data'], false);

        $this->assertEquals('report', $event->reportType);
        $this->assertEquals(['test' => 'data'], $event->parameters);
        $this->assertFalse($event->forceRefresh);
    }

    public function test_analytics_data_fetched_event()
    {
        $data = ['result' => 'data'];
        $event = new AnalyticsDataFetched('report', $data, ['params'], true);

        $this->assertEquals('report', $event->reportType);
        $this->assertEquals($data, $event->data);
        $this->assertEquals(['params'], $event->parameters);
        $this->assertTrue($event->fromCache);
    }

    public function test_analytics_cache_updated_event()
    {
        $data = ['cached' => 'data'];
        $event = new AnalyticsCacheUpdated('cache:key', 'report', $data);

        $this->assertEquals('cache:key', $event->cacheKey);
        $this->assertEquals('report', $event->reportType);
        $this->assertEquals($data, $event->data);
    }

    public function test_analytics_query_failed_event()
    {
        $exception = new Exception('Test error');
        $event = new AnalyticsQueryFailed('report', ['params'], $exception);

        $this->assertEquals('report', $event->reportType);
        $this->assertEquals(['params'], $event->parameters);
        $this->assertSame($exception, $event->exception);
    }
}
