<?php

namespace zfhassaan\genlytics\Services;

use Google\Analytics\Data\V1beta\RunReportResponse;
use Google\Analytics\Data\V1beta\RunRealtimeReportResponse;
use zfhassaan\genlytics\Contracts\DataTransformerInterface;

/**
 * Data Transformer Service
 * Following Single Responsibility Principle
 */
class DataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritDoc}
     */
    public function transformReport(RunReportResponse $response): array
    {
        $result = [];
        $rows = $response->getRows();

        if (empty($rows)) {
            return $result;
        }

        // Get dimension and metric headers
        $dimensionHeaders = $response->getDimensionHeaders() ?? [];
        $metricHeaders = $response->getMetricHeaders() ?? [];

        foreach ($rows as $row) {
            $rowData = ['dimensions' => [], 'metrics' => []];

            // Extract dimension values
            $dimensionValues = $row->getDimensionValues() ?? [];
            foreach ($dimensionValues as $index => $dimensionValue) {
                $headerName = isset($dimensionHeaders[$index]) && $dimensionHeaders[$index]
                    ? $dimensionHeaders[$index]->getName()
                    : "dimension_{$index}";
                $rowData['dimensions'][$headerName] = $dimensionValue->getValue();
            }

            // Extract metric values
            $metricValues = $row->getMetricValues() ?? [];
            foreach ($metricValues as $index => $metricValue) {
                $headerName = isset($metricHeaders[$index]) && $metricHeaders[$index]
                    ? $metricHeaders[$index]->getName()
                    : "metric_{$index}";
                $rowData['metrics'][$headerName] = $metricValue->getValue();
            }

            $result[] = $rowData;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function transformRealtimeReport(RunRealtimeReportResponse $response): array
    {
        $result = [];
        $rows = $response->getRows();

        if (empty($rows)) {
            return $result;
        }

        // Get dimension and metric headers
        $dimensionHeaders = $response->getDimensionHeaders() ?? [];
        $metricHeaders = $response->getMetricHeaders() ?? [];

        foreach ($rows as $row) {
            $rowData = ['dimensions' => [], 'metrics' => []];

            // Extract dimension values
            $dimensionValues = $row->getDimensionValues() ?? [];
            foreach ($dimensionValues as $index => $dimensionValue) {
                $headerName = isset($dimensionHeaders[$index]) && $dimensionHeaders[$index]
                    ? $dimensionHeaders[$index]->getName()
                    : "dimension_{$index}";
                $rowData['dimensions'][$headerName] = $dimensionValue->getValue();
            }

            // Extract metric values
            $metricValues = $row->getMetricValues() ?? [];
            foreach ($metricValues as $index => $metricValue) {
                $headerName = isset($metricHeaders[$index]) && $metricHeaders[$index]
                    ? $metricHeaders[$index]->getName()
                    : "metric_{$index}";
                $rowData['metrics'][$headerName] = $metricValue->getValue();
            }

            $result[] = $rowData;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function toJsonFormat(array $data, bool $includeMetadata = false): array
    {
        $formatted = [
            'data' => $data,
        ];

        if ($includeMetadata) {
            $formatted['metadata'] = [
                'count' => count($data),
                'timestamp' => now()->toIso8601String(),
            ];
        }

        return $formatted;
    }
}
