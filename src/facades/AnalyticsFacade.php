<?php

namespace zfhassaan\genlytics\facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string version()
 * @method static \zfhassaan\genlytics\Query\AnalyticsQueryBuilder query()
 * @method static \Illuminate\Support\Collection topPages(int $limit = 10, string|array $period = '30daysAgo')
 * @method static \Illuminate\Http\Resources\Json\AnonymousResourceCollection topPagesResource(int $limit = 10, string|array $period = '30daysAgo')
 * @method static \Illuminate\Support\Collection topCountries(int $limit = 10, string|array $period = '30daysAgo')
 * @method static \Illuminate\Http\Resources\Json\AnonymousResourceCollection topCountriesResource(int $limit = 10, string|array $period = '30daysAgo')
 * @method static \Illuminate\Support\Collection topCities(int $limit = 10, string|array $period = '30daysAgo')
 * @method static \Illuminate\Support\Collection trafficSources(int $limit = 10, string|array $period = '30daysAgo')
 * @method static \Illuminate\Http\Resources\Json\AnonymousResourceCollection trafficSourcesResource(int $limit = 10, string|array $period = '30daysAgo')
 * @method static \Illuminate\Support\Collection userEngagement(string|array $period = '30daysAgo')
 * @method static \Illuminate\Support\Collection deviceCategories(string|array $period = '30daysAgo')
 * @method static \Illuminate\Support\Collection topBrowsers(int $limit = 10, string|array $period = '30daysAgo')
 * @method static \Illuminate\Support\Collection conversions(int $limit = 10, string|array $period = '30daysAgo')
 * @method static \Illuminate\Support\Collection dailyTrend(int $days = 30)
 * @method static int activeUsers(string|array $period = '30daysAgo')
 * @method static int totalSessions(string|array $period = '30daysAgo')
 * @method static int realtimeActiveUsers()
 * @method static \Illuminate\Support\Collection realtimeTopPages(int $limit = 10)
 * @method static \Illuminate\Support\Collection realtimeTopCountries(int $limit = 10)
 * @method static \Illuminate\Support\Collection topProducts(int $limit = 10, string|array $period = '30daysAgo', ?int $ttl = null)
 * @method static \Illuminate\Http\Resources\Json\AnonymousResourceCollection topProductsResource(int $limit = 10, string|array $period = '30daysAgo', ?int $ttl = null)
 * @method static \Illuminate\Support\Collection topCategories(int $limit = 10, string|array $period = '30daysAgo', ?int $ttl = null)
 * @method static \Illuminate\Http\Resources\Json\AnonymousResourceCollection topCategoriesResource(int $limit = 10, string|array $period = '30daysAgo', ?int $ttl = null)
 * @method static \zfhassaan\genlytics\DTOs\FunnelPipelineDTO ecommerceFunnel(string|array $period = '30daysAgo', ?int $ttl = null)
 * @method static \zfhassaan\genlytics\Http\Resources\FunnelPipelineResource ecommerceFunnelResource(string|array $period = '30daysAgo', ?int $ttl = null)
 * @method static \zfhassaan\genlytics\DTOs\FunnelPipelineDTO funnel(array $steps, string $name = 'Conversion Funnel', string|array $period = '30daysAgo', ?int $ttl = null)
 * @method static \zfhassaan\genlytics\Http\Resources\FunnelPipelineResource funnelResource(array $steps, string $name = 'Conversion Funnel', string|array $period = '30daysAgo', ?int $ttl = null)
 * @method static \zfhassaan\genlytics\Http\Resources\QuotaStatusResource|null quotaStatusResource()
 * @method static \Illuminate\Http\JsonResponse runReports(array $period, $dimension, $metrics, array $options = [], bool $forceRefresh = false, ?int $customTtl = null)
 * @method static \Illuminate\Http\JsonResponse runRealTime($dimensions, $metrics, array $options = [], bool $forceRefresh = false, ?int $customTtl = null)
 * @method static \Illuminate\Http\JsonResponse batchReports(array $reports, bool $forceRefresh = false)
 * @method static \zfhassaan\genlytics\Contracts\CacheManagerInterface cache()
 * @method static \zfhassaan\genlytics\Genlytics setCacheTtl(int $seconds, ?string $type = null)
 * @method static int getCacheTtl(?string $type = null)
 * @method static \zfhassaan\genlytics\Contracts\QuotaManagerInterface|null quota()
 * @method static \zfhassaan\genlytics\DTOs\QuotaStatusDTO|null quotaStatus()
 * @method static int remainingTokens()
 * @method static \zfhassaan\genlytics\Genlytics enableMaximumQuotaUtilization()
 * @method static \zfhassaan\genlytics\Genlytics enableAllQuotaConsumption()
 * @method static \zfhassaan\genlytics\Genlytics consumeAllDailyQuota(bool $enable = true)
 * @method static bool isAllQuotaConsumptionEnabled()
 * @method static array drainDailyQuota(callable $executor, int $targetTokens = -1)
 * @method static \zfhassaan\genlytics\Genlytics setQuotaMode(string $mode)
 * @method static string getQuotaMode()
 * @method static bool isMaximumQuota()
 * @method static bool validateQuota(int $estimatedTokens = 1, ?string $ip = null, ?string $userId = null)
 *
 * @see \zfhassaan\genlytics\Genlytics
 */
class AnalyticsFacade extends Facade
{
    /**
     * Get the registered name of the component
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'genlytics';
    }
}
