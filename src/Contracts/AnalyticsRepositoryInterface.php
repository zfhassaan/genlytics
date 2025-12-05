<?php

namespace zfhassaan\genlytics\Contracts;

use Google\Analytics\Data\V1beta\RunReportResponse;
use Google\Analytics\Data\V1beta\RunRealtimeReportResponse;

/**
 * Interface for Analytics Repository
 * Following Repository Pattern and Dependency Inversion Principle
 */
interface AnalyticsRepositoryInterface
{
    /**
     * Run a report with specified date range, dimensions, and metrics
     *
     * @param array $dateRange
     * @param array $dimensions
     * @param array $metrics
     * @param array $options Additional options (filters, orderBy, etc.)
     * @return RunReportResponse
     */
    public function runReport(array $dateRange, array $dimensions, array $metrics, array $options = []): RunReportResponse;

    /**
     * Run a real-time report
     *
     * @param array $dimensions
     * @param array $metrics
     * @param array $options Additional options
     * @return RunRealtimeReportResponse
     */
    public function runRealtimeReport(array $dimensions, array $metrics, array $options = []): RunRealtimeReportResponse;

    /**
     * Run a dimension-only report
     *
     * @param array $dateRange
     * @param string|array $dimension
     * @param array $options Additional options
     * @return RunReportResponse
     */
    public function runDimensionReport(array $dateRange, $dimension, array $options = []): RunReportResponse;

    /**
     * Get property ID
     *
     * @return string
     */
    public function getPropertyId(): string;
}

