<?php

namespace zfhassaan\genlytics\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use zfhassaan\genlytics\Contracts\CacheManagerInterface;
use zfhassaan\genlytics\Events\AnalyticsCacheUpdated;
use zfhassaan\genlytics\Jobs\FetchAnalyticsDataJob;

/**
 * Listener for cache updates
 * Automatically refreshes real-time data
 */
class UpdateRealTimeCache implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event
     *
     * @param AnalyticsCacheUpdated $event
     * @return void
     */
    public function handle(AnalyticsCacheUpdated $event): void
    {
        // For real-time reports, schedule next update
        if ($event->reportType === 'realtime') {
            // Schedule next real-time update in 30 seconds
            FetchAnalyticsDataJob::dispatch($event->reportType, [], $event->cacheKey)
                ->delay(now()->addSeconds(30));
        }
    }
}
