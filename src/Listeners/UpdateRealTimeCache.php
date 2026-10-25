<?php

namespace zfhassaan\genlytics\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use zfhassaan\genlytics\Contracts\CacheManagerInterface;
use zfhassaan\genlytics\Events\AnalyticsCacheUpdated;
use zfhassaan\genlytics\Jobs\FetchAnalyticsDataJob;

/**
 * Listener for cache updates
 * Schedules real-time data refreshes
 */
class UpdateRealTimeCache implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected ?CacheManagerInterface $cacheManager = null
    ) {
    }

    /**
     * Handle the event
     *
     * @param AnalyticsCacheUpdated $event
     * @return void
     */
    public function handle(AnalyticsCacheUpdated $event): void
    {
        if ($event->reportType === 'realtime') {
            $interval = (int) config('analytics.realtime_cache_lifetime', 30);

            FetchAnalyticsDataJob::dispatch($event->reportType, $event->parameters, $event->cacheKey)
                ->delay(now()->addSeconds($interval));
        }
    }
}
