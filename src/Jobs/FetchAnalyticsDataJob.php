<?php

namespace zfhassaan\genlytics\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
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
 * Implements ShouldBeUnique for queue stampede protection
 * Following Single Responsibility Principle
 */
class FetchAnalyticsDataJob implements ShouldQueue, ShouldBeUnique
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
     * The number of seconds after which the unique lock expires.
     *
     * @var int
     */
    public int $uniqueFor = 120;

    /**
     * @param string $reportType Type of report (report, realtime, dimension, batch)
     * @param array $parameters Query parameters
     * @param string|null $cacheKey Optional cache key for updating
     */
    public function __construct(
        public string $reportType,
        public array $parameters,
        public ?string $cacheKey = null
    ) {
        $connection = config('analytics.queue.connection', config('analytics.queue_connection'));
        $queue = config('analytics.queue.name', config('analytics.queue_name', 'default'));
        $this->uniqueFor = (int) config('analytics.queue.unique_for', 120);

        if ($connection) {
            $this->onConnection($connection);
        }
        if ($queue) {
            $this->onQueue($queue);
        }
    }

    /**
     * The unique ID of the job to prevent queue stampedes.
     */
    public function uniqueId(): string
    {
        return $this->cacheKey ?? md5($this->reportType . json_encode($this->parameters));
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
                'report'    => $this->fetchReport($repository),
                'realtime'  => $this->fetchRealtimeReport($repository),
                'dimension' => $this->fetchDimensionReport($repository),
                'batch'     => $this->fetchBatchReport($repository),
                default     => throw new \InvalidArgumentException("Unknown report type: {$this->reportType}"),
            };

            // Transform the response
            $transformedData = match ($this->reportType) {
                'report', 'dimension' => $transformer->transformReport($response),
                'realtime'            => $transformer->transformRealtimeReport($response),
                'batch'               => $transformer->transformBatchReport($response),
            };

            // Cache the result
            $cacheKey = $this->cacheKey ?? $cacheManager->generateKey(
                $this->reportType,
                $this->parameters
            );

            if ($this->reportType === 'realtime') {
                $cacheManager->put($cacheKey, $transformedData, 30);
            } else {
                $cacheManager->put($cacheKey, $transformedData);
            }

            // Fire events
            event(new AnalyticsCacheUpdated($cacheKey, $this->reportType, $transformedData, $this->parameters));
            event(new AnalyticsDataFetched($this->reportType, $transformedData, $this->parameters, false));

        } catch (\Throwable $e) {
            Log::error('Analytics Job Failed', [
                'report_type' => $this->reportType,
                'parameters'  => $this->parameters,
                'error'       => $e->getMessage(),
            ]);

            event(new AnalyticsQueryFailed($this->reportType, $this->parameters, $e));

            throw $e;
        }
    }

    /**
     * Fetch standard report
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
     */
    protected function fetchDimensionReport(AnalyticsRepositoryInterface $repository)
    {
        return $repository->runDimensionReport(
            $this->parameters['dateRange'] ?? [],
            $this->parameters['dimension'] ?? '',
            $this->parameters['options'] ?? []
        );
    }

    /**
     * Fetch batch report
     */
    protected function fetchBatchReport(AnalyticsRepositoryInterface $repository)
    {
        return $repository->batchRunReports(
            $this->parameters['reports'] ?? []
        );
    }
}
