<?php

namespace zfhassaan\genlytics\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Event fired when analytics query fails
 */
class AnalyticsQueryFailed
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param string $reportType Type of report that failed
     * @param array $parameters Query parameters used
     * @param Throwable $exception The exception that occurred
     */
    public function __construct(
        public readonly string $reportType,
        public readonly array $parameters,
        public readonly Throwable $exception
    ) {
    }
}
