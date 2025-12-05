<?php

namespace zfhassaan\genlytics\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface;
use zfhassaan\genlytics\Contracts\CacheManagerInterface;
use zfhassaan\genlytics\Contracts\DataTransformerInterface;
use zfhassaan\genlytics\Events\AnalyticsDataFetched;
use zfhassaan\genlytics\Events\AnalyticsDataRequested;
use zfhassaan\genlytics\Events\AnalyticsQueryFailed;
use zfhassaan\genlytics\Jobs\FetchAnalyticsDataJob;
use Throwable;

/**
 * Analytics Service
 * Main service class following SOLID principles
 * Single Responsibility: Orchestrates analytics operations
 * Dependency Inversion: Depends on interfaces, not concrete classes
 */
class AnalyticsService
{
    protected bool $useBackgroundJobs;
    protected bool $enableCache;
    protected bool $enableRealTimeUpdates;

    /**
     * @param AnalyticsRepositoryInterface $repository
     * @param CacheManagerInterface $cacheManager
     * @param DataTransformerInterface $transformer
     * @param bool $useBackgroundJobs
     * @param bool $enableCache
     * @param bool $enableRealTimeUpdates
     */
    public function __construct(
        protected AnalyticsRepositoryInterface $repository,
        protected CacheManagerInterface $cacheManager,
        protected DataTransformerInterface $transformer,
        bool $useBackgroundJobs = true,
        bool $enableCache = true,
        bool $enableRealTimeUpdates = true
    ) {
        $this->useBackgroundJobs = $useBackgroundJobs;
        $this->enableCache = $enableCache;
        $this->enableRealTimeUpdates = $enableRealTimeUpdates;
    }

    /**
     * Run a report with caching and background processing support
     *
     * @param array $dateRange
     * @param array $dimensions
     * @param array $metrics
     * @param array $options
     * @param bool $forceRefresh
     * @return JsonResponse
     */
    public function runReports(
        array $dateRange,
        array $dimensions,
        array $metrics,
        array $options = [],
        bool $forceRefresh = false
    ): JsonResponse {
        try {
            $parameters = [
                'dateRange' => $dateRange,
                'dimensions' => $dimensions,
                'metrics' => $metrics,
                'options' => $options,
            ];

            event(new AnalyticsDataRequested('report', $parameters, $forceRefresh));

            // Generate cache key
            $cacheKey = $this->cacheManager->generateKey('report', $parameters);

            // Check cache first (unless force refresh)
            if ($this->enableCache && !$forceRefresh && $this->cacheManager->has($cacheKey)) {
                $cachedData = $this->cacheManager->get($cacheKey);
                
                // Trigger background refresh for next time
                if ($this->useBackgroundJobs) {
                    FetchAnalyticsDataJob::dispatch('report', $parameters, $cacheKey);
                }

                event(new AnalyticsDataFetched('report', $cachedData, $parameters, true));
                
                return $this->jsonResponse($cachedData);
            }

            // Fetch fresh data
            if ($this->useBackgroundJobs && !$forceRefresh) {
                // Dispatch job for background processing
                FetchAnalyticsDataJob::dispatch('report', $parameters, $cacheKey);
                
                // Return cached data if available, otherwise return empty with status
                if ($this->cacheManager->has($cacheKey)) {
                    $cachedData = $this->cacheManager->get($cacheKey);
                    return $this->jsonResponse($cachedData, ['status' => 'refreshing']);
                }
            }

            // Synchronous fetch
            $response = $this->repository->runReport($dateRange, $dimensions, $metrics, $options);
            $transformedData = $this->transformer->transformReport($response);

            // Cache the result
            if ($this->enableCache) {
                $this->cacheManager->put($cacheKey, $transformedData);
            }

            event(new AnalyticsDataFetched('report', $transformedData, $parameters, false));

            return $this->jsonResponse($transformedData);

        } catch (Throwable $e) {
            Log::error('Analytics Report Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            event(new AnalyticsQueryFailed('report', $parameters ?? [], $e));

            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Run real-time report
     *
     * @param array $dimensions
     * @param array $metrics
     * @param array $options
     * @param bool $forceRefresh
     * @return JsonResponse
     */
    public function runRealTime(
        array $dimensions,
        array $metrics,
        array $options = [],
        bool $forceRefresh = false
    ): JsonResponse {
        try {
            $parameters = [
                'dimensions' => $dimensions,
                'metrics' => $metrics,
                'options' => $options,
            ];

            event(new AnalyticsDataRequested('realtime', $parameters, $forceRefresh));

            // Real-time data typically shouldn't be cached for long
            // But we can cache for a few seconds to reduce API calls
            $cacheKey = $this->cacheManager->generateKey('realtime', $parameters);
            
            if ($this->enableCache && !$forceRefresh && $this->cacheManager->has($cacheKey)) {
                $cachedData = $this->cacheManager->get($cacheKey);
                
                // Always refresh real-time data in background
                if ($this->useBackgroundJobs) {
                    FetchAnalyticsDataJob::dispatch('realtime', $parameters, $cacheKey);
                }

                event(new AnalyticsDataFetched('realtime', $cachedData, $parameters, true));
                
                return $this->jsonResponse($cachedData);
            }

            // Fetch fresh real-time data
            $response = $this->repository->runRealtimeReport($dimensions, $metrics, $options);
            $transformedData = $this->transformer->transformRealtimeReport($response);

            // Cache for short duration (30 seconds for real-time)
            if ($this->enableCache) {
                $this->cacheManager->put($cacheKey, $transformedData, 30);
            }

            event(new AnalyticsDataFetched('realtime', $transformedData, $parameters, false));

            return $this->jsonResponse($transformedData);

        } catch (Throwable $e) {
            Log::error('Analytics Realtime Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            event(new AnalyticsQueryFailed('realtime', $parameters ?? [], $e));

            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Run dimension report
     *
     * @param array $dateRange
     * @param string|array $dimension
     * @param array $options
     * @param bool $forceRefresh
     * @return JsonResponse
     */
    public function runDimensionReport(
        array $dateRange,
        $dimension,
        array $options = [],
        bool $forceRefresh = false
    ): JsonResponse {
        try {
            $parameters = [
                'dateRange' => $dateRange,
                'dimension' => $dimension,
                'options' => $options,
            ];

            event(new AnalyticsDataRequested('dimension', $parameters, $forceRefresh));

            $cacheKey = $this->cacheManager->generateKey('dimension', $parameters);

            // Check cache
            if ($this->enableCache && !$forceRefresh && $this->cacheManager->has($cacheKey)) {
                $cachedData = $this->cacheManager->get($cacheKey);
                
                if ($this->useBackgroundJobs) {
                    FetchAnalyticsDataJob::dispatch('dimension', $parameters, $cacheKey);
                }

                event(new AnalyticsDataFetched('dimension', $cachedData, $parameters, true));
                
                return $this->jsonResponse($cachedData);
            }

            // Fetch fresh data
            $response = $this->repository->runDimensionReport($dateRange, $dimension, $options);
            $transformedData = $this->transformer->transformReport($response);

            // Cache the result
            if ($this->enableCache) {
                $this->cacheManager->put($cacheKey, $transformedData);
            }

            event(new AnalyticsDataFetched('dimension', $transformedData, $parameters, false));

            return $this->jsonResponse($transformedData);

        } catch (Throwable $e) {
            Log::error('Analytics Dimension Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            event(new AnalyticsQueryFailed('dimension', $parameters ?? [], $e));

            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create JSON response with formatted data
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

        return response()->json($response);
    }
}

