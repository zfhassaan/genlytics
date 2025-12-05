<?php

namespace zfhassaan\genlytics\Tests\Unit\Listeners;

use Illuminate\Support\Facades\Queue;
use Mockery;
use zfhassaan\genlytics\Events\AnalyticsCacheUpdated;
use zfhassaan\genlytics\Jobs\FetchAnalyticsDataJob;
use zfhassaan\genlytics\Listeners\UpdateRealTimeCache;
use zfhassaan\genlytics\Tests\TestCase;

class UpdateRealTimeCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_listener_schedules_next_update_for_realtime()
    {
        $event = new AnalyticsCacheUpdated('cache:key', 'realtime', ['data']);

        $listener = new UpdateRealTimeCache();
        $listener->handle($event);

        Queue::assertPushed(FetchAnalyticsDataJob::class, function ($job) {
            return $job->delay !== null;
        });
    }

    public function test_listener_does_not_schedule_for_non_realtime()
    {
        $event = new AnalyticsCacheUpdated('cache:key', 'report', ['data']);

        $listener = new UpdateRealTimeCache();
        $listener->handle($event);

        Queue::assertNothingPushed();
    }
}

