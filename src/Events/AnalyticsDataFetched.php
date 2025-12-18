<?php

namespace zfhassaan\genlytics\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when analytics data is successfully fetched
 */
class AnalyticsDataFetched
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param string $reportType Type of report (report, realtime, dimension)
     * @param array $data Fetched data
     * @param array $parameters Query parameters used
     * @param bool $fromCache Whether data came from cache
     */
    public function __construct(
        public readonly string $reportType,
        public readonly array $data,
        public readonly array $parameters,
        public readonly bool $fromCache = false
    ) {
    }
}
