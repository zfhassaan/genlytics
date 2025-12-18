<?php

namespace zfhassaan\genlytics\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when analytics data is requested
 * Useful for tracking usage and triggering background updates
 */
class AnalyticsDataRequested
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param string $reportType Type of report requested
     * @param array $parameters Query parameters
     * @param bool $forceRefresh Whether to bypass cache
     */
    public function __construct(
        public readonly string $reportType,
        public readonly array $parameters,
        public readonly bool $forceRefresh = false
    ) {
    }
}
