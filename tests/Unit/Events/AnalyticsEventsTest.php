<?php

namespace zfhassaan\genlytics\Tests\Unit\Events;

use Exception;
use zfhassaan\genlytics\DTOs\QuotaStatusDTO;
use zfhassaan\genlytics\Events\AnalyticsCacheUpdated;
use zfhassaan\genlytics\Events\AnalyticsDataFetched;
use zfhassaan\genlytics\Events\AnalyticsDataRequested;
use zfhassaan\genlytics\Events\AnalyticsQueryFailed;
use zfhassaan\genlytics\Events\QuotaUsageUpdated;
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

    public function test_quota_usage_updated_event()
    {
        $status = new QuotaStatusDTO([
            'property_type' => '360',
            'mode' => 'consume_all',
            'daily_tokens_limit' => 2000000,
            'daily_tokens_consumed' => 500000,
        ]);

        $event = new QuotaUsageUpdated($status, 25);
        $this->assertSame($status, $event->status);
        $this->assertEquals(25, $event->tokensConsumed);
        $this->assertEquals('genlytics-quota', $event->channel);
        $this->assertEquals('quota.updated', $event->broadcastAs());

        $payload = $event->broadcastWith();
        $this->assertEquals('360', $payload['property_type']);
        $this->assertEquals('consume_all', $payload['mode']);
        $this->assertEquals(2000000, $payload['daily_tokens_limit']);
        $this->assertEquals(25, $payload['tokens_consumed_this_event']);
        $this->assertTrue($payload['consume_all_active']);
    }
}
