<?php

namespace zfhassaan\genlytics\Contracts;

use Google\Analytics\Data\V1beta\RunReportResponse;
use Google\Analytics\Data\V1beta\RunRealtimeReportResponse;

/**
 * Interface for Data Transformation
 * Following Single Responsibility Principle
 */
interface DataTransformerInterface
{
    /**
     * Transform report response to array format
     *
     * @param RunReportResponse $response
     * @return array Transformed data
     */
    public function transformReport(RunReportResponse $response): array;

    /**
     * Transform real-time report response to array format
     *
     * @param RunRealtimeReportResponse $response
     * @return array Transformed data
     */
    public function transformRealtimeReport(RunRealtimeReportResponse $response): array;

    /**
     * Transform to JSON response format
     *
     * @param array $data Transformed data
     * @param bool $includeMetadata Include metadata in response
     * @return array JSON-ready array
     */
    public function toJsonFormat(array $data, bool $includeMetadata = false): array;
}
