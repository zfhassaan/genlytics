<?php

namespace zfhassaan\genlytics\Services;

use Illuminate\Support\Facades\Log;
use zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface;
use zfhassaan\genlytics\Contracts\CacheManagerInterface;
use zfhassaan\genlytics\Contracts\DataTransformerInterface;
use zfhassaan\genlytics\Events\AnalyticsCacheUpdated;
use zfhassaan\genlytics\Jobs\FetchAnalyticsDataJob;

/**
 * Real-Time Update Service
 * Manages automatic real-time data updates
 * Following Single Responsibility Principle
 */
class RealTimeUpdateService
{
    protected int $updateInterval;

    /**
     * @param AnalyticsRepositoryInterface $repository
     * @param CacheManagerInterface $cacheManager
     * @param DataTransformerInterface $transformer
     * @param int $updateInterval Update interval in seconds
     */
    public function __construct(
        protected AnalyticsRepositoryInterface $repository,
        protected CacheManagerInterface $cacheManager,
        protected DataTransformerInterface $transformer,
        int $updateInterval = 30
    ) {
        $this->updateInterval = $updateInterval;
    }

    /**
     * Schedule real-time updates for a query
     *
     * @param array $dimensions
     * @param array $metrics
     * @param array $options
     * @return void
     */
    public function scheduleUpdates(array $dimensions, array $metrics, array $options = []): void
    {
        $parameters = [
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'options' => $options,
        ];

        $cacheKey = $this->cacheManager->generateKey('realtime', $parameters);

        // Dispatch job with delay
        FetchAnalyticsDataJob::dispatch('realtime', $parameters, $cacheKey)
            ->delay(now()->addSeconds($this->updateInterval));
    }

    /**
     * Stop scheduled updates for a query
     * Note: This is a simplified implementation
     * In production, you might want to track scheduled jobs
     *
     * @param string $cacheKey
     * @return void
     */
    public function stopUpdates(string $cacheKey): void
    {
        // Clear the cache to stop updates
        $this->cacheManager->forget($cacheKey);
    }
}
