<?php

namespace zfhassaan\genlytics\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface;
use zfhassaan\genlytics\Contracts\CacheManagerInterface;
use zfhassaan\genlytics\Contracts\DataTransformerInterface;
use zfhassaan\genlytics\Events\AnalyticsCacheUpdated;
use zfhassaan\genlytics\Events\AnalyticsDataFetched;
use zfhassaan\genlytics\Events\AnalyticsQueryFailed;

/**
 * Background Job for Fetching Analytics Data
 * Following Single Responsibility Principle
 */
class FetchAnalyticsDataJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * @param string $reportType Type of report (report, realtime, dimension)
     * @param array $parameters Query parameters
     * @param string|null $cacheKey Optional cache key for updating
     */
    public function __construct(
        protected string $reportType,
        protected array $parameters,
        protected ?string $cacheKey = null
    ) {
        // Set queue connection and name from config
        $this->onConnection(config('analytics.queue_connection'));
        $this->onQueue(config('analytics.queue_name', 'default'));
    }

    /**
     * Execute the job
     *
     * @param AnalyticsRepositoryInterface $repository
     * @param CacheManagerInterface $cacheManager
     * @param DataTransformerInterface $transformer
     * @return void
     */
    public function handle(
        AnalyticsRepositoryInterface $repository,
        CacheManagerInterface $cacheManager,
        DataTransformerInterface $transformer
    ): void {
        try {
            $response = match ($this->reportType) {
                'report' => $this->fetchReport($repository),
                'realtime' => $this->fetchRealtimeReport($repository),
                'dimension' => $this->fetchDimensionReport($repository),
                default => throw new \InvalidArgumentException("Unknown report type: {$this->reportType}"),
            };

            // Transform the response
            $transformedData = match ($this->reportType) {
                'report', 'dimension' => $transformer->transformReport($response),
                'realtime' => $transformer->transformRealtimeReport($response),
            };

            // Cache the result
            $cacheKey = $this->cacheKey ?? $cacheManager->generateKey(
                $this->reportType,
                $this->parameters
            );

            $cacheManager->put($cacheKey, $transformedData);

            // Fire events
            event(new AnalyticsCacheUpdated($cacheKey, $this->reportType, $transformedData));
            event(new AnalyticsDataFetched($this->reportType, $transformedData, $this->parameters, false));

        } catch (\Exception $e) {
            Log::error('Analytics Job Failed', [
                'report_type' => $this->reportType,
                'parameters' => $this->parameters,
                'error' => $e->getMessage(),
            ]);

            event(new AnalyticsQueryFailed($this->reportType, $this->parameters, $e));

            throw $e;
        }
    }

    /**
     * Fetch standard report
     *
     * @param AnalyticsRepositoryInterface $repository
     * @return mixed
     */
    protected function fetchReport(AnalyticsRepositoryInterface $repository)
    {
        return $repository->runReport(
            $this->parameters['dateRange'] ?? [],
            $this->parameters['dimensions'] ?? [],
            $this->parameters['metrics'] ?? [],
            $this->parameters['options'] ?? []
        );
    }

    /**
     * Fetch real-time report
     *
     * @param AnalyticsRepositoryInterface $repository
     * @return mixed
     */
    protected function fetchRealtimeReport(AnalyticsRepositoryInterface $repository)
    {
        return $repository->runRealtimeReport(
            $this->parameters['dimensions'] ?? [],
            $this->parameters['metrics'] ?? [],
            $this->parameters['options'] ?? []
        );
    }

    /**
     * Fetch dimension report
     *
     * @param AnalyticsRepositoryInterface $repository
     * @return mixed
     */
    protected function fetchDimensionReport(AnalyticsRepositoryInterface $repository)
    {
        return $repository->runDimensionReport(
            $this->parameters['dateRange'] ?? [],
            $this->parameters['dimension'] ?? '',
            $this->parameters['options'] ?? []
        );
    }
}
