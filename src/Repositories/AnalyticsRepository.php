<?php

namespace zfhassaan\genlytics\Repositories;

use Google\Analytics\Data\V1beta\BatchRunReportsResponse;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\RunReportResponse;
use Google\Analytics\Data\V1beta\RunRealtimeReportResponse;
use Google\ApiCore\ApiException;
use Illuminate\Support\Facades\Log;
use zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface;
use zfhassaan\genlytics\Contracts\QuotaManagerInterface;
use zfhassaan\genlytics\overrides\BetaAnalyticsDataClient;

/**
 * Analytics Repository Implementation
 * Following Repository Pattern and Single Responsibility Principle
 */
class AnalyticsRepository implements AnalyticsRepositoryInterface
{
    protected string $propertyId;
    protected BetaAnalyticsDataClient $client;
    protected ?QuotaManagerInterface $quotaManager = null;

    /**
     * @param BetaAnalyticsDataClient $client
     * @param string $propertyId
     * @param QuotaManagerInterface|null $quotaManager
     */
    public function __construct(
        BetaAnalyticsDataClient $client,
        string $propertyId,
        ?QuotaManagerInterface $quotaManager = null
    ) {
        $this->client = $client;
        $this->propertyId = $propertyId;
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

    /**
     * {@inheritDoc}
     */
    public function runReport(array $dateRange, array $dimensions, array $metrics, array $options = []): RunReportResponse
    {
        $slotAcquired = false;
        if ($this->quotaManager !== null) {
            $slotAcquired = $this->quotaManager->acquireConcurrencySlot();
        }

        try {
            $dimensionObjects = $this->buildDimensions($dimensions);
            $metricObjects = $this->buildMetrics($metrics);
            $dateRangeObjects = $this->buildDateRanges($dateRange);

            $requestOptions = array_merge([
                'property' => $this->propertyId,
                'dateRanges' => $dateRangeObjects,
                'dimensions' => $dimensionObjects,
                'metrics' => $metricObjects,
            ], $options);

            $response = $this->client->runReport($requestOptions);

            if ($this->quotaManager !== null) {
                if (method_exists($response, 'getPropertyQuota') && $response->getPropertyQuota() !== null) {
                    $this->quotaManager->syncFromPropertyQuota($response->getPropertyQuota());
                } else {
                    $this->quotaManager->recordUsage(1);
                }
            }

            return $response;
        } catch (ApiException $e) {
            Log::error('Analytics API Error', [
                'method' => 'runReport',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw $e;
        } finally {
            if ($slotAcquired && $this->quotaManager !== null) {
                $this->quotaManager->releaseConcurrencySlot();
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function runRealtimeReport(array $dimensions, array $metrics, array $options = []): RunRealtimeReportResponse
    {
        $slotAcquired = false;
        if ($this->quotaManager !== null) {
            $slotAcquired = $this->quotaManager->acquireConcurrencySlot();
        }

        try {
            $dimensionObjects = $this->buildDimensions($dimensions);
            $metricObjects = $this->buildMetrics($metrics);

            $requestOptions = array_merge([
                'property' => $this->propertyId,
                'dimensions' => $dimensionObjects,
                'metrics' => $metricObjects,
            ], $options);

            $response = $this->client->runRealtimeReport($requestOptions);

            if ($this->quotaManager !== null) {
                if (method_exists($response, 'getPropertyQuota') && $response->getPropertyQuota() !== null) {
                    $this->quotaManager->syncFromPropertyQuota($response->getPropertyQuota());
                } else {
                    $this->quotaManager->recordUsage(1);
                }
            }

            return $response;
        } catch (ApiException $e) {
            Log::error('Analytics Realtime API Error', [
                'method' => 'runRealtimeReport',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw $e;
        } finally {
            if ($slotAcquired && $this->quotaManager !== null) {
                $this->quotaManager->releaseConcurrencySlot();
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function runDimensionReport(array $dateRange, $dimension, array $options = []): RunReportResponse
    {
        try {
            $dimensionObjects = is_array($dimension)
                ? $this->buildDimensions($dimension)
                : [new Dimension(['name' => $dimension])];

            $dateRangeObjects = $this->buildDateRanges($dateRange);

            $requestOptions = array_merge([
                'property' => $this->propertyId,
                'dateRanges' => $dateRangeObjects,
                'dimensions' => $dimensionObjects,
            ], $options);

            return $this->client->runReport($requestOptions);
        } catch (ApiException $e) {
            Log::error('Analytics Dimension API Error', [
                'method' => 'runDimensionReport',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function batchRunReports(array $reports): BatchRunReportsResponse
    {
        $slotAcquired = false;
        if ($this->quotaManager !== null) {
            $slotAcquired = $this->quotaManager->acquireConcurrencySlot();
        }

        try {
            $requests = [];
            foreach ($reports as $report) {
                $dimensionObjects = isset($report['dimensions']) ? $this->buildDimensions($report['dimensions']) : [];
                $metricObjects = isset($report['metrics']) ? $this->buildMetrics($report['metrics']) : [];
                $dateRangeObjects = isset($report['dateRange']) ? $this->buildDateRanges($report['dateRange']) : [];

                $reqData = [
                    'property' => $this->propertyId,
                    'date_ranges' => $dateRangeObjects,
                    'dimensions' => $dimensionObjects,
                    'metrics' => $metricObjects,
                ];

                if (!empty($report['options'])) {
                    $reqData = array_merge($reqData, $report['options']);
                }

                $requests[] = new RunReportRequest($reqData);
            }

            $response = $this->client->batchRunReports([
                'property' => $this->propertyId,
                'requests' => $requests,
            ]);

            if ($this->quotaManager !== null) {
                $this->quotaManager->recordUsage(count($requests));
            }

            return $response;
        } catch (ApiException $e) {
            Log::error('Analytics Batch API Error', [
                'method' => 'batchRunReports',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw $e;
        } finally {
            if ($slotAcquired && $this->quotaManager !== null) {
                $this->quotaManager->releaseConcurrencySlot();
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyId(): string
    {
        return $this->propertyId;
    }

    /**
     * Build Dimension objects from array
     *
     * @param array $dimensions
     * @return array<Dimension>
     */
    protected function buildDimensions(array $dimensions): array
    {
        return array_map(function ($dimension) {
            if ($dimension instanceof Dimension) {
                return $dimension;
            }
            return new Dimension(is_array($dimension) ? $dimension : ['name' => $dimension]);
        }, $dimensions);
    }

    /**
     * Build Metric objects from array
     *
     * @param array $metrics
     * @return array<Metric>
     */
    protected function buildMetrics(array $metrics): array
    {
        return array_map(function ($metric) {
            if ($metric instanceof Metric) {
                return $metric;
            }
            return new Metric(is_array($metric) ? $metric : ['name' => $metric]);
        }, $metrics);
    }

    /**
     * Build DateRange objects from array
     *
     * @param array $dateRanges
     * @return array<DateRange>
     */
    protected function buildDateRanges(array $dateRanges): array
    {
        // Handle single date range or multiple
        if (isset($dateRanges['start_date']) && isset($dateRanges['end_date'])) {
            $dateRanges = [$dateRanges];
        }

        return array_map(function ($dateRange) {
            if ($dateRange instanceof DateRange) {
                return $dateRange;
            }
            return new DateRange($dateRange);
        }, $dateRanges);
    }
}
