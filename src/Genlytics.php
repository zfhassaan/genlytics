<?php

namespace zfhassaan\genlytics;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use zfhassaan\genlytics\DTOs\CategoryPerformanceDTO;
use zfhassaan\genlytics\DTOs\FunnelPipelineDTO;
use zfhassaan\genlytics\DTOs\FunnelStepDTO;
use zfhassaan\genlytics\DTOs\ProductPerformanceDTO;
use zfhassaan\genlytics\DTOs\QuotaStatusDTO;
use zfhassaan\genlytics\Contracts\QuotaManagerInterface;
use zfhassaan\genlytics\Http\Resources\AnalyticsResourceCollection;
use zfhassaan\genlytics\Http\Resources\CategoryPerformanceResource;
use zfhassaan\genlytics\Http\Resources\CountryReportResource;
use zfhassaan\genlytics\Http\Resources\FunnelPipelineResource;
use zfhassaan\genlytics\Http\Resources\PageReportResource;
use zfhassaan\genlytics\Http\Resources\ProductPerformanceResource;
use zfhassaan\genlytics\Http\Resources\QuotaStatusResource;
use zfhassaan\genlytics\Http\Resources\TrafficSourceResource;
use zfhassaan\genlytics\Query\AnalyticsQueryBuilder;
use zfhassaan\genlytics\Services\AnalyticsService;

/**
 * Genlytics Main Class
 * Facade-friendly wrapper providing both low-level report querying,
 * dedicated helper queries, and an Eloquent-like fluent query builder.
 */
class Genlytics
{
    /**
     * Package version
     */
    public const VERSION = '1.0.5';

    protected AnalyticsService $service;

    /**
     * @param AnalyticsService $service
     */
    public function __construct(AnalyticsService $service)
    {
        $this->service = $service;
    }

    /**
     * Get the current Genlytics package version
     */
    public static function version(): string
    {
        return self::VERSION;
    }

    /**
     * Start a new fluent query builder instance
     */
    public function query(): AnalyticsQueryBuilder
    {
        return new AnalyticsQueryBuilder($this->service);
    }

    // =========================================================================
    // Dedicated Optimized Query Methods
    // =========================================================================

    /**
     * Get top pages by page views and active users
     */
    public function topPages(int $limit = 10, string|array $period = '30daysAgo'): Collection
    {
        $range = $this->normalizePeriod($period);

        return $this->query()
            ->period($range['start_date'], $range['end_date'])
            ->dimensions('pagePath', 'pageTitle')
            ->metrics('screenPageViews', 'activeUsers', 'averageSessionDuration')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top countries by active users and sessions
     */
    public function topCountries(int $limit = 10, string|array $period = '30daysAgo'): Collection
    {
        $range = $this->normalizePeriod($period);

        return $this->query()
            ->period($range['start_date'], $range['end_date'])
            ->dimension('country')
            ->metrics('activeUsers', 'sessions')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top cities by active users
     */
    public function topCities(int $limit = 10, string|array $period = '30daysAgo'): Collection
    {
        $range = $this->normalizePeriod($period);

        return $this->query()
            ->period($range['start_date'], $range['end_date'])
            ->dimensions('city', 'country')
            ->metrics('activeUsers', 'sessions')
            ->limit($limit)
            ->get();
    }

    /**
     * Get traffic acquisition sources (sessionSource, sessionMedium)
     */
    public function trafficSources(int $limit = 10, string|array $period = '30daysAgo'): Collection
    {
        $range = $this->normalizePeriod($period);

        return $this->query()
            ->period($range['start_date'], $range['end_date'])
            ->dimensions('sessionSource', 'sessionMedium')
            ->metrics('sessions', 'activeUsers', 'bounceRate')
            ->limit($limit)
            ->get();
    }

    /**
     * Get overall user engagement metrics
     */
    public function userEngagement(string|array $period = '30daysAgo'): Collection
    {
        $range = $this->normalizePeriod($period);

        return $this->query()
            ->period($range['start_date'], $range['end_date'])
            ->metrics('activeUsers', 'newUsers', 'engagedSessions', 'engagementRate', 'userEngagementDuration')
            ->get();
    }

    /**
     * Get breakdown of traffic by device categories (mobile, desktop, tablet)
     */
    public function deviceCategories(string|array $period = '30daysAgo'): Collection
    {
        $range = $this->normalizePeriod($period);

        return $this->query()
            ->period($range['start_date'], $range['end_date'])
            ->dimension('deviceCategory')
            ->metrics('activeUsers', 'sessions')
            ->get();
    }

    /**
     * Get top web browsers
     */
    public function topBrowsers(int $limit = 10, string|array $period = '30daysAgo'): Collection
    {
        $range = $this->normalizePeriod($period);

        return $this->query()
            ->period($range['start_date'], $range['end_date'])
            ->dimension('browser')
            ->metrics('activeUsers', 'sessions')
            ->limit($limit)
            ->get();
    }

    /**
     * Get conversions and event counts by event name
     */
    public function conversions(int $limit = 10, string|array $period = '30daysAgo'): Collection
    {
        $range = $this->normalizePeriod($period);

        return $this->query()
            ->period($range['start_date'], $range['end_date'])
            ->dimension('eventName')
            ->metrics('eventCount', 'conversions')
            ->limit($limit)
            ->get();
    }

    /**
     * Get daily trend of active users and views over the last N days
     */
    public function dailyTrend(int $days = 30): Collection
    {
        return $this->query()
            ->period("{$days}daysAgo", 'today')
            ->dimension('date')
            ->metrics('activeUsers', 'sessions', 'screenPageViews')
            ->orderBy('date', 'ASC')
            ->get();
    }

    /**
     * Get total active users for a period as integer
     */
    public function activeUsers(string|array $period = '30daysAgo'): int
    {
        $range = $this->normalizePeriod($period);

        $row = $this->query()
            ->period($range['start_date'], $range['end_date'])
            ->metric('activeUsers')
            ->first();

        return (int) ($row['metrics']['activeUsers'] ?? 0);
    }

    /**
     * Get total sessions for a period as integer
     */
    public function totalSessions(string|array $period = '30daysAgo'): int
    {
        $range = $this->normalizePeriod($period);

        $row = $this->query()
            ->period($range['start_date'], $range['end_date'])
            ->metric('sessions')
            ->first();

        return (int) ($row['metrics']['sessions'] ?? 0);
    }

    /**
     * Get active users in real-time (last 30 minutes)
     */
    public function realtimeActiveUsers(): int
    {
        $result = $this->query()
            ->realtime()
            ->metric('activeUsers')
            ->first();

        return (int) ($result['metrics']['activeUsers'] ?? 0);
    }

    /**
     * Get top pages currently active in real-time
     */
    public function realtimeTopPages(int $limit = 10): Collection
    {
        return $this->query()
            ->realtime()
            ->dimension('pagePath')
            ->metric('activeUsers')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top countries currently active in real-time
     */
    public function realtimeTopCountries(int $limit = 10): Collection
    {
        return $this->query()
            ->realtime()
            ->dimension('country')
            ->metric('activeUsers')
            ->limit($limit)
            ->get();
    }

    // =========================================================================
    // E-Commerce & Funnel Analytics
    // =========================================================================

    /**
     * Get top performing products ranked by revenue, views, and items purchased
     *
     * @param int $limit
     * @param string|array $period
     * @param int|null $ttl Optional custom cache lifetime in seconds
     * @return Collection<int, ProductPerformanceDTO>
     */
    public function topProducts(int $limit = 10, string|array $period = '30daysAgo', ?int $ttl = null): Collection
    {
        $range = $this->normalizePeriod($period);

        $query = $this->query()
            ->period($range['start_date'], $range['end_date'])
            ->dimensions('itemId', 'itemName', 'itemCategory')
            ->metrics('itemRevenue', 'itemsPurchased', 'itemsViewed', 'itemsAddedToCart')
            ->orderBy('itemRevenue', 'DESC')
            ->limit($limit);

        if ($ttl !== null) {
            $query->remember($ttl);
        }

        return $query->toProductDto();
    }

    /**
     * Get top performing product categories ranked by revenue and units sold
     *
     * @param int $limit
     * @param string|array $period
     * @param int|null $ttl Optional custom cache lifetime in seconds
     * @return Collection<int, CategoryPerformanceDTO>
     */
    public function topCategories(int $limit = 10, string|array $period = '30daysAgo', ?int $ttl = null): Collection
    {
        $range = $this->normalizePeriod($period);

        $query = $this->query()
            ->period($range['start_date'], $range['end_date'])
            ->dimension('itemCategory')
            ->metrics('itemRevenue', 'itemsPurchased', 'itemsViewed', 'itemsAddedToCart')
            ->orderBy('itemRevenue', 'DESC')
            ->limit($limit);

        if ($ttl !== null) {
            $query->remember($ttl);
        }

        return $query->toCategoryDto();
    }

    /**
     * Get the default Google Analytics 4 E-Commerce Purchase Funnel pipeline
     * (view_item -> add_to_cart -> begin_checkout -> purchase)
     * with step-by-step drop-off and conversion rates.
     *
     * @param string|array $period
     * @param int|null $ttl Optional custom cache lifetime in seconds
     * @return FunnelPipelineDTO
     */
    public function ecommerceFunnel(string|array $period = '30daysAgo', ?int $ttl = null): FunnelPipelineDTO
    {
        $defaultSteps = [
            '1. View Item'      => 'view_item',
            '2. Add to Cart'    => 'add_to_cart',
            '3. Begin Checkout' => 'begin_checkout',
            '4. Purchase'       => 'purchase',
        ];

        return $this->funnel($defaultSteps, 'E-Commerce Purchase Funnel', $period, $ttl);
    }

    /**
     * Analyze any custom conversion funnel pipeline with sequential drop-offs
     *
     * @param array $steps Associative array ['Step Label' => 'event_name'] or list of event names
     * @param string $name Funnel pipeline name
     * @param string|array $period
     * @param int|null $ttl Optional custom cache lifetime in seconds
     * @return FunnelPipelineDTO
     */
    public function funnel(array $steps, string $name = 'Conversion Funnel', string|array $period = '30daysAgo', ?int $ttl = null): FunnelPipelineDTO
    {
        $range = $this->normalizePeriod($period);

        // Normalize step mapping
        $stepDefinitions = [];
        foreach ($steps as $key => $value) {
            if (is_numeric($key)) {
                $stepDefinitions[$value] = $value;
            } else {
                $stepDefinitions[$key] = $value;
            }
        }

        // Query event counts from GA4
        $query = $this->query()
            ->period($range['start_date'], $range['end_date'])
            ->dimension('eventName')
            ->metric('eventCount');

        if ($ttl !== null) {
            $query->remember($ttl);
        }

        $rows = $query->get();

        $eventCounts = [];
        foreach ($rows as $row) {
            $eName = $row['dimensions']['eventName'] ?? null;
            if ($eName) {
                $eventCounts[$eName] = (int) ($row['metrics']['eventCount'] ?? 0);
            }
        }

        $stepDTOs = [];
        $totalEntries = 0;
        $prevCount = 0;
        $stepIndex = 1;

        foreach ($stepDefinitions as $label => $eventName) {
            $count = $eventCounts[$eventName] ?? 0;

            if ($stepIndex === 1) {
                $totalEntries = $count;
                $dropOffCount = 0;
                $dropOffRate = 0.0;
                $stepConversionRate = 100.0;
                $overallConversionRate = 100.0;
            } else {
                $dropOffCount = max(0, $prevCount - $count);
                $dropOffRate = $prevCount > 0 ? round(($dropOffCount / $prevCount) * 100, 2) : 0.0;
                $stepConversionRate = $prevCount > 0 ? round(($count / $prevCount) * 100, 2) : 0.0;
                $overallConversionRate = $totalEntries > 0 ? round(($count / $totalEntries) * 100, 2) : 0.0;
            }

            $stepDTOs[] = new FunnelStepDTO(
                step: $stepIndex,
                name: $label,
                eventName: $eventName,
                count: $count,
                dropOffCount: $dropOffCount,
                dropOffRate: $dropOffRate,
                conversionRate: $stepConversionRate,
                overallConversionRate: $overallConversionRate
            );

            $prevCount = $count;
            $stepIndex++;
        }

        $totalCompletions = !empty($stepDTOs) ? end($stepDTOs)->count : 0;
        $pipelineOverallConversion = $totalEntries > 0 ? round(($totalCompletions / $totalEntries) * 100, 2) : 0.0;

        return new FunnelPipelineDTO(
            name: $name,
            totalEntries: $totalEntries,
            totalCompletions: $totalCompletions,
            overallConversionRate: $pipelineOverallConversion,
            steps: collect($stepDTOs)
        );
    }

    // =========================================================================
    // API Resource Return Helpers (with Package Version)
    // =========================================================================

    /**
     * Get top pages as standardized API Resource collection
     */
    public function topPagesResource(int $limit = 10, string|array $period = '30daysAgo'): AnonymousResourceCollection
    {
        $range = $this->normalizePeriod($period);
        return $this->query()
            ->period($range['start_date'], $range['end_date'])
            ->dimensions('pagePath', 'pageTitle')
            ->metrics('screenPageViews', 'activeUsers', 'averageSessionDuration')
            ->orderBy('screenPageViews', 'DESC')
            ->limit($limit)
            ->toPageResource();
    }

    /**
     * Get top countries as standardized API Resource collection
     */
    public function topCountriesResource(int $limit = 10, string|array $period = '30daysAgo'): AnonymousResourceCollection
    {
        $range = $this->normalizePeriod($period);
        return $this->query()
            ->period($range['start_date'], $range['end_date'])
            ->dimension('country')
            ->metrics('activeUsers', 'sessions')
            ->orderBy('activeUsers', 'DESC')
            ->limit($limit)
            ->toCountryResource();
    }

    /**
     * Get traffic sources as standardized API Resource collection
     */
    public function trafficSourcesResource(int $limit = 10, string|array $period = '30daysAgo'): AnonymousResourceCollection
    {
        $range = $this->normalizePeriod($period);
        return $this->query()
            ->period($range['start_date'], $range['end_date'])
            ->dimensions('sessionSource', 'sessionMedium')
            ->metrics('sessions', 'activeUsers', 'bounceRate')
            ->limit($limit)
            ->toTrafficSourceResource();
    }

    /**
     * Get top products as standardized API Resource collection
     */
    public function topProductsResource(int $limit = 10, string|array $period = '30daysAgo', ?int $ttl = null): AnonymousResourceCollection
    {
        $range = $this->normalizePeriod($period);
        $query = $this->query()
            ->period($range['start_date'], $range['end_date'])
            ->dimensions('itemId', 'itemName', 'itemCategory')
            ->metrics('itemRevenue', 'itemsPurchased', 'itemsViewed', 'itemsAddedToCart')
            ->orderBy('itemRevenue', 'DESC')
            ->limit($limit);

        if ($ttl !== null) {
            $query->remember($ttl);
        }

        return $query->toProductResource();
    }

    /**
     * Get top categories as standardized API Resource collection
     */
    public function topCategoriesResource(int $limit = 10, string|array $period = '30daysAgo', ?int $ttl = null): AnonymousResourceCollection
    {
        $range = $this->normalizePeriod($period);
        $query = $this->query()
            ->period($range['start_date'], $range['end_date'])
            ->dimension('itemCategory')
            ->metrics('itemRevenue', 'itemsPurchased', 'itemsViewed', 'itemsAddedToCart')
            ->orderBy('itemRevenue', 'DESC')
            ->limit($limit);

        if ($ttl !== null) {
            $query->remember($ttl);
        }

        return $query->toCategoryResource();
    }

    /**
     * Get e-commerce funnel pipeline as standardized API Resource
     */
    public function ecommerceFunnelResource(string|array $period = '30daysAgo', ?int $ttl = null): FunnelPipelineResource
    {
        return new FunnelPipelineResource($this->ecommerceFunnel($period, $ttl));
    }

    /**
     * Get custom funnel pipeline as standardized API Resource
     */
    public function funnelResource(array $steps, string $name = 'Conversion Funnel', string|array $period = '30daysAgo', ?int $ttl = null): FunnelPipelineResource
    {
        return new FunnelPipelineResource($this->funnel($steps, $name, $period, $ttl));
    }

    /**
     * Get GA4 quota and pacing status as standardized API Resource
     */
    public function quotaStatusResource(): ?QuotaStatusResource
    {
        $status = $this->quotaStatus();
        return $status !== null ? new QuotaStatusResource($status) : null;
    }

    // =========================================================================
    // Core Report Execution
    // =========================================================================

    /**
     * Run Report for any Dimension and Metrics (returns JsonResponse)
     */
    public function runReports(
        array $period,
        $dimension,
        $metrics,
        array $options = [],
        bool $forceRefresh = false
    ): JsonResponse {
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
     * Get Report as pure array (avoids JSON serialization overhead)
     */
    public function getReport(
        array $period,
        $dimension,
        $metrics,
        array $options = [],
        bool $forceRefresh = false
    ): array {
        $dimensions = $this->normalizeToArray($dimension);
        $metricsArray = $this->normalizeToArray($metrics);

        return $this->service->getReport(
            $period,
            $dimensions,
            $metricsArray,
            $options,
            $forceRefresh
        );
    }

    /**
     * Get Real Time Data from Analytics (returns JsonResponse)
     */
    public function runRealTime(
        $dimension,
        $metrics,
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
     * Get Real Time Data as pure array
     */
    public function getRealTime(
        $dimension,
        $metrics,
        array $options = [],
        bool $forceRefresh = false
    ): array {
        $dimensions = $this->normalizeToArray($dimension);
        $metricsArray = $this->normalizeToArray($metrics);

        return $this->service->getRealTime(
            $dimensions,
            $metricsArray,
            $options,
            $forceRefresh
        );
    }

    /**
     * Run only Dimensions for Analytics with Time Duration (returns JsonResponse)
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
     * Get Dimension Report as pure array
     */
    public function getDimensionReport(
        array $period,
        $dimension,
        array $options = [],
        bool $forceRefresh = false
    ): array {
        return $this->service->getDimensionReport(
            $period,
            $dimension,
            $options,
            $forceRefresh
        );
    }

    /**
     * Run multiple reports in a single batch request (returns JsonResponse)
     */
    public function batchRunReports(array $reports, bool $forceRefresh = false): JsonResponse
    {
        return $this->service->batchRunReports($reports, $forceRefresh);
    }

    /**
     * Get Batch Reports as pure array
     */
    public function getBatchReports(array $reports, bool $forceRefresh = false): array
    {
        return $this->service->getBatchReports($reports, $forceRefresh);
    }

    /**
     * Get the underlying analytics service
     */
    public function getService(): AnalyticsService
    {
        return $this->service;
    }

    /**
     * Normalize period input
     */
    protected function normalizePeriod(string|array $period): array
    {
        if (is_string($period)) {
            return ['start_date' => $period, 'end_date' => 'today'];
        }

        if (isset($period['start_date']) && isset($period['end_date'])) {
            return $period;
        }

        return ['start_date' => '30daysAgo', 'end_date' => 'today'];
    }

    /**
     * Normalize input to array format
     */
    protected function normalizeToArray($input): array
    {
        if (empty($input)) {
            return [];
        }

        if (is_array($input) && isset($input[0]) && is_array($input[0])) {
            return $input;
        }

        if (is_array($input) && isset($input['name'])) {
            return [$input];
        }

        if (is_string($input)) {
            return [['name' => $input]];
        }

        return is_array($input) ? $input : [];
    }

    /**
     * Get the GA4 Quota & Rate Limit Manager
     */
    public function quota(): ?QuotaManagerInterface
    {
        return $this->service->quota();
    }

    /**
     * Get the current GA4 Quota & Pacing Status DTO
     */
    public function quotaStatus(): ?QuotaStatusDTO
    {
        return $this->service->quota()?->getStatus();
    }

    /**
     * Get remaining daily core tokens (out of 200,000 standard or 2,000,000 360)
     */
    public function remainingTokens(): int
    {
        return $this->service->quota()?->getRemainingDailyTokens() ?? 200000;
    }

    /**
     * Enable Maximum Quota Utilization Mode.
     * Allows consuming up to 100% of available tokens without premature throttling.
     */
    public function enableMaximumQuotaUtilization(): self
    {
        $this->service->quota()?->enableMaximumUtilization();
        return $this;
    }

    /**
     * Enable Full Daily Quota Consumption Mode (unlocks 100% of daily tokens).
     */
    public function enableAllQuotaConsumption(): self
    {
        $this->service->quota()?->enableAllQuotaConsumption();
        return $this;
    }

    /**
     * Toggle whether queries can consume all daily quota without falling back to stale cache.
     */
    public function consumeAllDailyQuota(bool $enable = true): self
    {
        $this->service->quota()?->setConsumeAllDailyQuota($enable);
        return $this;
    }

    /**
     * Check if full daily quota consumption is enabled.
     */
    public function isAllQuotaConsumptionEnabled(): bool
    {
        return $this->service->quota()?->shouldConsumeAllDailyQuota() ?? false;
    }

    /**
     * Drain remaining daily quota through automated pre-warming tasks.
     *
     * @param callable $executor
     * @param int $targetTokens
     * @return array
     */
    public function drainDailyQuota(callable $executor, int $targetTokens = -1): array
    {
        return $this->service->quota()?->drainDailyQuota($executor, $targetTokens) ?? [
            'initial_remaining' => 0,
            'target_tokens' => 0,
            'total_consumed' => 0,
            'final_remaining' => 0,
            'operations_executed' => 0,
            'errors' => ['Quota manager unavailable'],
        ];
    }

    /**
     * Set operational quota mode ('maximum_utilization', 'balanced', 'conservative', 'consume_all').
     */
    public function setQuotaMode(string $mode): self
    {
        $this->service->quota()?->setMode($mode);
        return $this;
    }

    /**
     * Get current quota mode.
     */
    public function getQuotaMode(): string
    {
        return $this->service->quota()?->getMode() ?? 'balanced';
    }

    /**
     * Check if maximum quota utilization is active.
     */
    public function isMaximumQuota(): bool
    {
        return $this->service->quota()?->isMaximumUtilization() ?? false;
    }

    /**
     * Validate pre-flight query execution against Google API limits.
     */
    public function validateQuota(int $estimatedTokens = 1, ?string $ip = null, ?string $userId = null): bool
    {
        return $this->service->quota()?->validateExecution($estimatedTokens, $ip, $userId) ?? true;
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
        $this->service->setCacheTtl($seconds, $type);
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
        return $this->service->getCacheTtl($type);
    }

    /**
     * Get the underlying CacheManager instance.
     */
    public function cache(): \zfhassaan\genlytics\Contracts\CacheManagerInterface
    {
        return $this->service->getCacheManager();
    }
}
