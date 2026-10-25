<?php

namespace zfhassaan\genlytics\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;
use zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface;
use zfhassaan\genlytics\Contracts\CacheManagerInterface;
use zfhassaan\genlytics\Contracts\DataTransformerInterface;
use zfhassaan\genlytics\Contracts\QuotaManagerInterface;
use zfhassaan\genlytics\DTOs\QuotaStatusDTO;
use zfhassaan\genlytics\Exceptions\QuotaExceededException;
use zfhassaan\genlytics\Events\AnalyticsDataFetched;
use zfhassaan\genlytics\Events\AnalyticsDataRequested;
use zfhassaan\genlytics\Events\AnalyticsQueryFailed;
use zfhassaan\genlytics\Jobs\FetchAnalyticsDataJob;

/**
 * Analytics Service
 * Main service class for orchestrating analytics operations
 * Single Responsibility: Orchestrates analytics operations
 * Dependency Inversion: Depends on interfaces, not concrete classes
 */
class AnalyticsService
{
    protected bool $useBackgroundJobs;
    protected bool $enableCache;
    protected bool $enableRealTimeUpdates;
    protected int $lockDuration;
    protected int $realtimeTtl;
    protected ?QuotaManagerInterface $quotaManager = null;

    /**
     * @param AnalyticsRepositoryInterface $repository
     * @param CacheManagerInterface $cacheManager
     * @param DataTransformerInterface $transformer
     * @param bool $useBackgroundJobs
     * @param bool $enableCache
     * @param bool $enableRealTimeUpdates
     * @param int $lockDuration
     * @param QuotaManagerInterface|null $quotaManager
     */
    public function __construct(
        protected AnalyticsRepositoryInterface $repository,
        protected CacheManagerInterface $cacheManager,
        protected DataTransformerInterface $transformer,
        bool $useBackgroundJobs = false,
        bool $enableCache = true,
        bool $enableRealTimeUpdates = false,
        int $lockDuration = 60,
        ?QuotaManagerInterface $quotaManager = null
    ) {
        $this->useBackgroundJobs = $useBackgroundJobs;
        $this->enableCache = $enableCache;
        $this->enableRealTimeUpdates = $enableRealTimeUpdates;
        $this->lockDuration = $lockDuration;
        $this->realtimeTtl = (int) config('analytics.realtime_cache_lifetime', 30);
        $this->quotaManager = $quotaManager;
    }

    public function getQuotaManager(): ?QuotaManagerInterface
    {
        return $this->quotaManager;
    }

    public function setQuotaManager(QuotaManagerInterface $quotaManager): void
    {
        $this->quotaManager = $quotaManager;
    }

    public function quota(): ?QuotaManagerInterface
    {
        return $this->quotaManager;
    }

    /**
     * Run a report and return JsonResponse (maintains backward compatibility)
     *
     * @param array $dateRange
     * @param array $dimensions
     * @param array $metrics
     * @param array $options
     * @param bool $forceRefresh
     * @param int|null $customTtl Custom cache TTL in seconds
     * @return JsonResponse
     */
    public function runReports(
        array $dateRange,
        array $dimensions,
        array $metrics,
        array $options = [],
        bool $forceRefresh = false,
        ?int $customTtl = null
    ): JsonResponse {
        try {
            $data = $this->getReport($dateRange, $dimensions, $metrics, $options, $forceRefresh, $customTtl);
            return $this->jsonResponse($data);
        } catch (Throwable $e) {
            return $this->errorResponse('report', [
                'dateRange' => $dateRange,
                'dimensions' => $dimensions,
                'metrics' => $metrics,
                'options' => $options,
            ], $e);
        }
    }

    /**
     * Get raw report data as an array
     *
     * @param array $dateRange
     * @param array $dimensions
     * @param array $metrics
     * @param array $options
     * @param bool $forceRefresh
     * @param int|null $customTtl Custom cache TTL in seconds
     * @return array
     */
    public function getReport(
        array $dateRange,
        array $dimensions,
        array $metrics,
        array $options = [],
        bool $forceRefresh = false,
        ?int $customTtl = null
    ): array {
        $parameters = [
            'dateRange' => $dateRange,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'options' => $options,
        ];

        event(new AnalyticsDataRequested('report', $parameters, $forceRefresh));

        $cacheKey = $this->cacheManager->generateKey('report', $parameters);

        // Quota check & Adaptive Pacing
        if ($this->quotaManager !== null && !$this->quotaManager->canExecute()) {
            if ($this->enableCache && $this->cacheManager->has($cacheKey)) {
                $cachedData = $this->cacheManager->get($cacheKey);
                event(new AnalyticsDataFetched('report', $cachedData, $parameters, true));
                return $cachedData;
            }

            $strategy = config('analytics.quota.exhaustion_strategy', 'cache_fallback');
            if ($strategy === 'strict') {
                throw new QuotaExceededException(
                    'core_tokens',
                    200000,
                    200000,
                    $this->quotaManager->getSecondsUntilDailyReset()
                );
            }
        }

        // Check cache (Stale-While-Revalidate pattern or Adaptive Pacing Stale Serving)
        $shouldServeStale = $this->quotaManager !== null && $this->quotaManager->shouldUseStaleCache();
        if ($this->enableCache && (!$forceRefresh || $shouldServeStale) && $this->cacheManager->has($cacheKey)) {
            $cachedData = $this->cacheManager->get($cacheKey);

            if (!$shouldServeStale) {
                // Trigger background refresh if lock acquired (prevents thundering herd)
                $this->dispatchBackgroundRefreshIfLocked('report', $parameters, $cacheKey);
            }

            event(new AnalyticsDataFetched('report', $cachedData, $parameters, true));

            return $cachedData;
        }

        // Synchronous fetch
        $response = $this->repository->runReport($dateRange, $dimensions, $metrics, $options);
        $transformedData = $this->transformer->transformReport($response);

        if ($this->enableCache) {
            if ($customTtl !== null) {
                $this->cacheManager->put($cacheKey, $transformedData, $customTtl);
            } else {
                $this->cacheManager->put($cacheKey, $transformedData);
            }
        }

        event(new AnalyticsDataFetched('report', $transformedData, $parameters, false));

        return $transformedData;
    }

    /**
     * Run real-time report and return JsonResponse
     *
     * @param array $dimensions
     * @param array $metrics
     * @param array $options
     * @param bool $forceRefresh
     * @param int|null $customTtl Custom cache TTL in seconds
     * @return JsonResponse
     */
    public function runRealTime(
        array $dimensions,
        array $metrics,
        array $options = [],
        bool $forceRefresh = false,
        ?int $customTtl = null
    ): JsonResponse {
        try {
            $data = $this->getRealTime($dimensions, $metrics, $options, $forceRefresh, $customTtl);
            return $this->jsonResponse($data);
        } catch (Throwable $e) {
            return $this->errorResponse('realtime', [
                'dimensions' => $dimensions,
                'metrics' => $metrics,
                'options' => $options,
            ], $e);
        }
    }

    /**
     * Get raw real-time report data as an array
     *
     * @param array $dimensions
     * @param array $metrics
     * @param array $options
     * @param bool $forceRefresh
     * @param int|null $customTtl Custom cache TTL in seconds
     * @return array
     */
    public function getRealTime(
        array $dimensions,
        array $metrics,
        array $options = [],
        bool $forceRefresh = false,
        ?int $customTtl = null
    ): array {
        $parameters = [
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'options' => $options,
        ];

        event(new AnalyticsDataRequested('realtime', $parameters, $forceRefresh));

        $cacheKey = $this->cacheManager->generateKey('realtime', $parameters);

        if ($this->quotaManager !== null && !$this->quotaManager->canExecute()) {
            if ($this->enableCache && $this->cacheManager->has($cacheKey)) {
                $cachedData = $this->cacheManager->get($cacheKey);
                event(new AnalyticsDataFetched('realtime', $cachedData, $parameters, true));
                return $cachedData;
            }
        }

        if ($this->enableCache && !$forceRefresh && $this->cacheManager->has($cacheKey)) {
            $cachedData = $this->cacheManager->get($cacheKey);

            $this->dispatchBackgroundRefreshIfLocked('realtime', $parameters, $cacheKey);

            event(new AnalyticsDataFetched('realtime', $cachedData, $parameters, true));

            return $cachedData;
        }

        $response = $this->repository->runRealtimeReport($dimensions, $metrics, $options);
        $transformedData = $this->transformer->transformRealtimeReport($response);

        if ($this->enableCache) {
            $ttl = $customTtl ?? $this->realtimeTtl;
            $this->cacheManager->put($cacheKey, $transformedData, $ttl);
        }

        event(new AnalyticsDataFetched('realtime', $transformedData, $parameters, false));

        return $transformedData;
    }

    /**
     * Run dimension-only report and return JsonResponse
     *
     * @param array $dateRange
     * @param string|array $dimension
     * @param array $options
     * @param bool $forceRefresh
     * @param int|null $customTtl Custom cache TTL in seconds
     * @return JsonResponse
     */
    public function runDimensionReport(
        array $dateRange,
        $dimension,
        array $options = [],
        bool $forceRefresh = false,
        ?int $customTtl = null
    ): JsonResponse {
        try {
            $data = $this->getDimensionReport($dateRange, $dimension, $options, $forceRefresh, $customTtl);
            return $this->jsonResponse($data);
        } catch (Throwable $e) {
            return $this->errorResponse('dimension', [
                'dateRange' => $dateRange,
                'dimension' => $dimension,
                'options' => $options,
            ], $e);
        }
    }

    /**
     * Get raw dimension report data as an array
     *
     * @param array $dateRange
     * @param string|array $dimension
     * @param array $options
     * @param bool $forceRefresh
     * @param int|null $customTtl Custom cache TTL in seconds
     * @return array
     */
    public function getDimensionReport(
        array $dateRange,
        $dimension,
        array $options = [],
        bool $forceRefresh = false,
        ?int $customTtl = null
    ): array {
        $parameters = [
            'dateRange' => $dateRange,
            'dimension' => $dimension,
            'options' => $options,
        ];

        event(new AnalyticsDataRequested('dimension', $parameters, $forceRefresh));

        $cacheKey = $this->cacheManager->generateKey('dimension', $parameters);

        if ($this->enableCache && !$forceRefresh && $this->cacheManager->has($cacheKey)) {
            $cachedData = $this->cacheManager->get($cacheKey);

            $this->dispatchBackgroundRefreshIfLocked('dimension', $parameters, $cacheKey);

            event(new AnalyticsDataFetched('dimension', $cachedData, $parameters, true));

            return $cachedData;
        }

        $response = $this->repository->runDimensionReport($dateRange, $dimension, $options);
        $transformedData = $this->transformer->transformReport($response);

        if ($this->enableCache) {
            if ($customTtl !== null) {
                $this->cacheManager->put($cacheKey, $transformedData, $customTtl);
            } else {
                $this->cacheManager->put($cacheKey, $transformedData);
            }
        }

        event(new AnalyticsDataFetched('dimension', $transformedData, $parameters, false));

        return $transformedData;
    }

    /**
     * Run multiple reports in a single batch call and return JsonResponse
     *
     * @param array $reports Array of report queries
     * @param bool $forceRefresh
     * @param int|null $customTtl Custom cache TTL in seconds
     * @return JsonResponse
     */
    public function batchRunReports(
        array $reports,
        bool $forceRefresh = false,
        ?int $customTtl = null
    ): JsonResponse {
        try {
            $data = $this->getBatchReports($reports, $forceRefresh, $customTtl);
            return $this->jsonResponse($data);
        } catch (Throwable $e) {
            return $this->errorResponse('batch', ['reports' => $reports], $e);
        }
    }

    /**
     * Get raw batch report data as an array
     *
     * @param array $reports
     * @param bool $forceRefresh
     * @param int|null $customTtl Custom cache TTL in seconds
     * @return array
     */
    public function getBatchReports(
        array $reports,
        bool $forceRefresh = false,
        ?int $customTtl = null
    ): array {
        $parameters = ['reports' => $reports];

        event(new AnalyticsDataRequested('batch', $parameters, $forceRefresh));

        $cacheKey = $this->cacheManager->generateKey('batch', $parameters);

        if ($this->enableCache && !$forceRefresh && $this->cacheManager->has($cacheKey)) {
            $cachedData = $this->cacheManager->get($cacheKey);

            $this->dispatchBackgroundRefreshIfLocked('batch', $parameters, $cacheKey);

            event(new AnalyticsDataFetched('batch', $cachedData, $parameters, true));

            return $cachedData;
        }

        $response = $this->repository->batchRunReports($reports);
        $transformedData = $this->transformer->transformBatchReport($response);

        if ($this->enableCache) {
            if ($customTtl !== null) {
                $this->cacheManager->put($cacheKey, $transformedData, $customTtl);
            } else {
                $this->cacheManager->put($cacheKey, $transformedData);
            }
        }

        event(new AnalyticsDataFetched('batch', $transformedData, $parameters, false));

        return $transformedData;
    }

    /**
     * Set cache expiry lifetime in seconds dynamically.
     *
     * @param int $seconds
     * @param string|null $type (report, realtime, dimension, batch, funnel, ecommerce)
     * @return self
     */
    public function setCacheTtl(int $seconds, ?string $type = null): self
    {
        if ($type === 'realtime') {
            $this->realtimeTtl = $seconds;
        }
        $this->cacheManager->setLifetime($seconds, $type);
        return $this;
    }

    /**
     * Get configured cache expiry lifetime in seconds.
     *
     * @param string|null $type
     * @return int
     */
    public function getCacheTtl(?string $type = null): int
    {
        if ($type === 'realtime') {
            return $this->realtimeTtl;
        }
        return $this->cacheManager->getLifetime($type);
    }

    /**
     * Get the underlying CacheManager instance.
     */
    public function getCacheManager(): CacheManagerInterface
    {
        return $this->cacheManager;
    }

    /**
     * Dispatch background refresh job with atomic lock protection
     */
    protected function dispatchBackgroundRefreshIfLocked(string $type, array $parameters, string $cacheKey): void
    {
        if (!$this->useBackgroundJobs) {
            return;
        }

        $lockKey = "swr:{$cacheKey}";
        $lock = $this->cacheManager->lock($lockKey, $this->lockDuration);

        // If lock is supported, only dispatch if lock is successfully acquired
        if ($lock) {
            if ($lock->get()) {
                FetchAnalyticsDataJob::dispatch($type, $parameters, $cacheKey);
            }
        } else {
            FetchAnalyticsDataJob::dispatch($type, $parameters, $cacheKey);
        }
    }

    /**
     * Create JSON response with formatted data and package version
     *
     * @param array $data
     * @param array $metadata
     * @return JsonResponse
     */
    protected function jsonResponse(array $data, array $metadata = []): JsonResponse
    {
        $response = $this->transformer->toJsonFormat($data, true);

        if (!empty($metadata)) {
            $response['metadata'] = array_merge($response['metadata'] ?? [], $metadata);
        }

        return response()->json($response)->header('X-Genlytics-Version', \zfhassaan\genlytics\Genlytics::VERSION);
    }

    /**
     * Format and log error response
     */
    protected function errorResponse(string $type, array $parameters, Throwable $e): JsonResponse
    {
        Log::error("Analytics {$type} Error", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        event(new AnalyticsQueryFailed($type, $parameters, $e));

        return response()->json([
            'status' => false,
            'error' => $e->getMessage(),
        ], 400);
    }
}
