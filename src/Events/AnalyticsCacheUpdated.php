<?php

namespace zfhassaan\genlytics\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when analytics cache is updated
 */
class AnalyticsCacheUpdated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param string $cacheKey Cache key that was updated
     * @param string $reportType Type of report
     * @param array $data Cached data
     */
    public function __construct(
        public readonly string $cacheKey,
        public readonly string $reportType,
        public readonly array $data
    ) {
    }
}
