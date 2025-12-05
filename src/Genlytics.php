<?php

namespace zfhassaan\genlytics;

use Illuminate\Http\JsonResponse;
use zfhassaan\genlytics\Services\AnalyticsService;

/**
 * Genlytics Main Class
 * Facade-friendly wrapper that maintains backward compatibility
 * Following Facade Pattern and Adapter Pattern
 */
class Genlytics
{
    protected AnalyticsService $service;

    /**
     * @param AnalyticsService $service
     */
    public function __construct(AnalyticsService $service)
    {
        $this->service = $service;
    }

    /**
     * Run Report for any Dimension and Metrics
     * Maintains backward compatibility with original API
     *
     * @param array $period Date range with 'start_date' and 'end_date'
     * @param array $dimension Single dimension array or array of dimensions
     * @param array $metrics Single metric array or array of metrics
     * @param array $options Additional options
     * @param bool $forceRefresh Force refresh from API
     * @return JsonResponse
     */
    public function runReports(
        array $period,
        array $dimension,
        array $metrics,
        array $options = [],
        bool $forceRefresh = false
    ): JsonResponse {
        // Normalize dimensions and metrics to arrays
        $dimensions = $this->normalizeToArray($dimension);
        $metricsArray = $this->normalizeToArray($metrics);

        return $this->service->runReports(
            $period,
            $dimensions,
            $metricsArray,
            $options,
            $forceRefresh
        );
    }

    /**
     * Get Real Time Data from Analytics
     *
     * @param array $dimension Single dimension array or array of dimensions
     * @param array $metrics Single metric array or array of metrics
     * @param array $options Additional options
     * @param bool $forceRefresh Force refresh from API
     * @return JsonResponse
     */
    public function runRealTime(
        array $dimension,
        array $metrics,
        array $options = [],
        bool $forceRefresh = false
    ): JsonResponse {
        $dimensions = $this->normalizeToArray($dimension);
        $metricsArray = $this->normalizeToArray($metrics);

        return $this->service->runRealTime(
            $dimensions,
            $metricsArray,
            $options,
            $forceRefresh
        );
    }

    /**
     * Run only Dimensions for Analytics with Time Duration
     *
     * @param array $period Date range with 'start_date' and 'end_date'
     * @param string|array $dimension Dimension name or array
     * @param array $options Additional options
     * @param bool $forceRefresh Force refresh from API
     * @return JsonResponse
     */
    public function runDimensionReport(
        array $period,
        $dimension,
        array $options = [],
        bool $forceRefresh = false
    ): JsonResponse {
        return $this->service->runDimensionReport(
            $period,
            $dimension,
            $options,
            $forceRefresh
        );
    }

    /**
     * Get the underlying analytics service
     * Useful for advanced usage
     *
     * @return AnalyticsService
     */
    public function getService(): AnalyticsService
    {
        return $this->service;
    }

    /**
     * Normalize input to array format
     *
     * @param mixed $input
     * @return array
     */
    protected function normalizeToArray($input): array
    {
        if (empty($input)) {
            return [];
        }

        // If already an array of arrays, return as is
        if (is_array($input) && isset($input[0]) && is_array($input[0])) {
            return $input;
        }

        // If single array with 'name' key, wrap in array
        if (is_array($input) && isset($input['name'])) {
            return [$input];
        }

        // If string, convert to array format
        if (is_string($input)) {
            return [['name' => $input]];
        }

        // If already array, return as is
        return is_array($input) ? $input : [];
    }
}
