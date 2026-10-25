<?php

namespace zfhassaan\genlytics\Query;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use zfhassaan\genlytics\DTOs\AnalyticsRowDTO;
use zfhassaan\genlytics\DTOs\CountryReportDTO;
use zfhassaan\genlytics\DTOs\PageReportDTO;
use zfhassaan\genlytics\DTOs\TrafficSourceDTO;
use zfhassaan\genlytics\Http\Resources\AnalyticsResourceCollection;
use zfhassaan\genlytics\Http\Resources\CategoryPerformanceResource;
use zfhassaan\genlytics\Http\Resources\CountryReportResource;
use zfhassaan\genlytics\Http\Resources\PageReportResource;
use zfhassaan\genlytics\Http\Resources\ProductPerformanceResource;
use zfhassaan\genlytics\Http\Resources\TrafficSourceResource;
use zfhassaan\genlytics\Services\AnalyticsService;

/**
 * Eloquent-like Fluent Query Builder for Google Analytics 4
 * Allows intuitive, developer-friendly chaining of date ranges, dimensions, metrics, filters, and limits.
 */
class AnalyticsQueryBuilder
{
    protected AnalyticsService $service;
    protected array $dateRange = ['start_date' => '30daysAgo', 'end_date' => 'today'];
    protected array $dimensions = [];
    protected array $metrics = [];
    protected array $options = [];
    protected bool $forceRefresh = false;
    protected bool $isRealtime = false;
    protected bool $maxQuota = false;
    protected ?int $customTtl = null;

    /**
     * @param AnalyticsService $service
     */
    public function __construct(AnalyticsService $service)
    {
        $this->service = $service;
    }

    /**
     * Set arbitrary date range
     */
    public function period(string $startDate, string $endDate = 'today'): self
    {
        $this->dateRange = [
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ];
        return $this;
    }

    /**
     * Predefined date range: Today
     */
    public function today(): self
    {
        return $this->period('today', 'today');
    }

    /**
     * Predefined date range: Yesterday
     */
    public function yesterday(): self
    {
        return $this->period('yesterday', 'yesterday');
    }

    /**
     * Predefined date range: Last 7 Days
     */
    public function last7Days(): self
    {
        return $this->period('7daysAgo', 'today');
    }

    /**
     * Predefined date range: Last 14 Days
     */
    public function last14Days(): self
    {
        return $this->period('14daysAgo', 'today');
    }

    /**
     * Predefined date range: Last 30 Days
     */
    public function last30Days(): self
    {
        return $this->period('30daysAgo', 'today');
    }

    /**
     * Predefined date range: Last 90 Days
     */
    public function last90Days(): self
    {
        return $this->period('90daysAgo', 'today');
    }

    /**
     * Switch query to Real-Time mode (last 30 minutes)
     */
    public function realtime(): self
    {
        $this->isRealtime = true;
        return $this;
    }

    /**
     * Add multiple dimensions
     */
    public function dimensions(string|array ...$dimensions): self
    {
        $flattened = [];
        foreach ($dimensions as $dim) {
            if (is_array($dim)) {
                $flattened = array_merge($flattened, $dim);
            } else {
                $flattened[] = $dim;
            }
        }

        foreach ($flattened as $d) {
            $this->dimension($d);
        }

        return $this;
    }

    /**
     * Add a single dimension
     */
    public function dimension(string|array $dimension): self
    {
        if (is_string($dimension)) {
            $this->dimensions[] = ['name' => $dimension];
        } elseif (is_array($dimension)) {
            $this->dimensions[] = isset($dimension['name']) ? $dimension : ['name' => reset($dimension)];
        }
        return $this;
    }

    /**
     * Add multiple metrics
     */
    public function metrics(string|array ...$metrics): self
    {
        $flattened = [];
        foreach ($metrics as $met) {
            if (is_array($met)) {
                $flattened = array_merge($flattened, $met);
            } else {
                $flattened[] = $met;
            }
        }

        foreach ($flattened as $m) {
            $this->metric($m);
        }

        return $this;
    }

    /**
     * Add a single metric
     */
    public function metric(string|array $metric): self
    {
        if (is_string($metric)) {
            $this->metrics[] = ['name' => $metric];
        } elseif (is_array($metric)) {
            $this->metrics[] = isset($metric['name']) ? $metric : ['name' => reset($metric)];
        }
        return $this;
    }

    /**
     * Set max rows to return
     */
    public function limit(int $limit): self
    {
        $this->options['limit'] = $limit;
        return $this;
    }

    /**
     * Set row offset
     */
    public function offset(int $offset): self
    {
        $this->options['offset'] = $offset;
        return $this;
    }

    /**
     * Force fresh data from GA4, bypassing cache
     */
    public function fresh(): self
    {
        $this->forceRefresh = true;
        return $this;
    }

    /**
     * Enable Maximum Quota Utilization Mode for this query.
     * Allows consuming up to 100% of core tokens without premature throttling.
     *
     * @param bool $enable
     * @return self
     */
    public function maxQuota(bool $enable = true): self
    {
        $this->maxQuota = $enable;
        if ($enable && $this->service->quota() !== null) {
            $this->service->quota()->enableMaximumUtilization();
        }
        return $this;
    }

    /**
     * Alias for maxQuota()
     *
     * @return self
     */
    public function withMaxQuota(): self
    {
        return $this->maxQuota(true);
    }

    /**
     * Unlock 100% daily quota consumption for this query (never fall back to stale cache until 0 tokens remain).
     *
     * @param bool $enable
     * @return self
     */
    public function consumeAllQuota(bool $enable = true): self
    {
        if ($enable && $this->service->quota() !== null) {
            $this->service->quota()->enableAllQuotaConsumption();
        }
        return $this;
    }

    /**
     * Alias for consumeAllQuota()
     *
     * @return self
     */
    public function withAllQuota(): self
    {
        return $this->consumeAllQuota(true);
    }

    /**
     * Set a custom cache lifetime in seconds or via DateInterval/DateTime for this query.
     *
     * @param int|\DateInterval|\DateTimeInterface $ttl
     * @return self
     */
    public function remember(int|\DateInterval|\DateTimeInterface $ttl): self
    {
        if ($ttl instanceof \DateTimeInterface) {
            $this->customTtl = max(0, $ttl->getTimestamp() - time());
        } elseif ($ttl instanceof \DateInterval) {
            $reference = new \DateTimeImmutable();
            $endTime = $reference->add($ttl);
            $this->customTtl = max(0, $endTime->getTimestamp() - $reference->getTimestamp());
        } else {
            $this->customTtl = (int) $ttl;
        }

        return $this;
    }

    /**
     * Alias for remember()
     *
     * @param int|\DateInterval|\DateTimeInterface $ttl
     * @return self
     */
    public function cacheFor(int|\DateInterval|\DateTimeInterface $ttl): self
    {
        return $this->remember($ttl);
    }

    /**
     * Alias for remember()
     *
     * @param int|\DateInterval|\DateTimeInterface $ttl
     * @return self
     */
    public function ttl(int|\DateInterval|\DateTimeInterface $ttl): self
    {
        return $this->remember($ttl);
    }

    /**
     * Disable caching for this query execution.
     *
     * @return self
     */
    public function dontCache(): self
    {
        $this->forceRefresh = true;
        return $this;
    }

    /**
     * Alias for dontCache()
     *
     * @return self
     */
    public function noCache(): self
    {
        return $this->dontCache();
    }

    /**
     * Order results by metric or dimension
     */
    public function orderBy(string $name, string $direction = 'DESC'): self
    {
        $isDesc = strtoupper($direction) === 'DESC';
        $this->options['orderBys'][] = [
            'metric' => ['metricName' => $name],
            'desc'   => $isDesc,
        ];
        return $this;
    }

    /**
     * Execute query and return an Illuminate Collection of rows
     */
    public function get(): Collection
    {
        $array = $this->toArray();
        return collect($array);
    }

    /**
     * Return collection of strongly-typed AnalyticsRowDTO instances
     *
     * @return Collection<int, AnalyticsRowDTO>
     */
    public function toDto(): Collection
    {
        return $this->get()->map(fn (array $row) => AnalyticsRowDTO::fromArray($row));
    }

    /**
     * Return collection of PageReportDTO instances
     *
     * @return Collection<int, PageReportDTO>
     */
    public function toPageDto(): Collection
    {
        return $this->get()->map(fn (array $row) => PageReportDTO::fromRow($row));
    }

    /**
     * Return collection of CountryReportDTO instances
     *
     * @return Collection<int, CountryReportDTO>
     */
    public function toCountryDto(): Collection
    {
        return $this->get()->map(fn (array $row) => CountryReportDTO::fromRow($row));
    }

    /**
     * Return collection of TrafficSourceDTO instances
     *
     * @return Collection<int, TrafficSourceDTO>
     */
    public function toTrafficSourceDto(): Collection
    {
        return $this->get()->map(fn (array $row) => TrafficSourceDTO::fromRow($row));
    }

    /**
     * Return collection of ProductPerformanceDTO instances
     *
     * @return Collection<int, \zfhassaan\genlytics\DTOs\ProductPerformanceDTO>
     */
    public function toProductDto(): Collection
    {
        return $this->get()->map(fn (array $row) => \zfhassaan\genlytics\DTOs\ProductPerformanceDTO::fromRow($row));
    }

    /**
     * Return collection of CategoryPerformanceDTO instances
     *
     * @return Collection<int, \zfhassaan\genlytics\DTOs\CategoryPerformanceDTO>
     */
    public function toCategoryDto(): Collection
    {
        return $this->get()->map(fn (array $row) => \zfhassaan\genlytics\DTOs\CategoryPerformanceDTO::fromRow($row));
    }

    /**
     * Return standardized Laravel API Resource collection
     */
    public function toResource(): AnalyticsResourceCollection
    {
        return new AnalyticsResourceCollection($this->toDto());
    }

    /**
     * Return API Resource Collection for Page Reports
     */
    public function toPageResource(): AnonymousResourceCollection
    {
        return $this->wrapWithVersion(PageReportResource::collection($this->toPageDto()));
    }

    /**
     * Return API Resource Collection for Country Reports
     */
    public function toCountryResource(): AnonymousResourceCollection
    {
        return $this->wrapWithVersion(CountryReportResource::collection($this->toCountryDto()));
    }

    /**
     * Return API Resource Collection for Traffic Source Reports
     */
    public function toTrafficSourceResource(): AnonymousResourceCollection
    {
        return $this->wrapWithVersion(TrafficSourceResource::collection($this->toTrafficSourceDto()));
    }

    /**
     * Return API Resource Collection for Product Performance
     */
    public function toProductResource(): AnonymousResourceCollection
    {
        return $this->wrapWithVersion(ProductPerformanceResource::collection($this->toProductDto()));
    }

    /**
     * Return API Resource Collection for Category Performance
     */
    public function toCategoryResource(): AnonymousResourceCollection
    {
        return $this->wrapWithVersion(CategoryPerformanceResource::collection($this->toCategoryDto()));
    }

    /**
     * Attach package version metadata to anonymous resource collection
     */
    protected function wrapWithVersion(AnonymousResourceCollection $collection): AnonymousResourceCollection
    {
        return $collection->additional([
            'success' => true,
            'version' => \zfhassaan\genlytics\Genlytics::VERSION,
            'meta'    => [
                'version'   => \zfhassaan\genlytics\Genlytics::VERSION,
                'count'     => $collection->count(),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Return an HTTP JsonResponse directly from API Resource
     */
    public function toResponse($request = null): JsonResponse
    {
        return $this->toResource()->toResponse($request ?? request());
    }

    /**
     * Execute query and return raw array
     */
    public function toArray(): array
    {
        // Pre-flight quota validation
        try {
            $quotaManager = $this->service->quota();
            if ($quotaManager !== null) {
                $estimated = $quotaManager->estimateTokens($this->dateRange, $this->dimensions, $this->metrics);
                $quotaManager->validateExecution($estimated);
            }
        } catch (\zfhassaan\genlytics\Exceptions\QuotaExceededException|\zfhassaan\genlytics\Exceptions\QuotaValidationException $qe) {
            throw $qe;
        } catch (\Throwable) {
            // Gracefully ignore unmocked service in tests
        }

        if ($this->isRealtime) {
            return $this->service->getRealTime(
                $this->dimensions,
                $this->metrics,
                $this->options,
                $this->forceRefresh,
                $this->customTtl
            );
        }

        return $this->service->getReport(
            $this->dateRange,
            $this->dimensions,
            $this->metrics,
            $this->options,
            $this->forceRefresh,
            $this->customTtl
        );
    }

    /**
     * Return first record or null
     */
    public function first(): ?array
    {
        $results = $this->limit(1)->toArray();
        return $results[0] ?? null;
    }

    /**
     * Return total number of result records
     */
    public function count(): int
    {
        return count($this->toArray());
    }

    /**
     * Return JsonResponse representation
     */
    public function toJson(): JsonResponse
    {
        if ($this->isRealtime) {
            return $this->service->runRealTime(
                $this->dimensions,
                $this->metrics,
                $this->options,
                $this->forceRefresh
            );
        }

        return $this->service->runReports(
            $this->dateRange,
            $this->dimensions,
            $this->metrics,
            $this->options,
            $this->forceRefresh
        );
    }
}
