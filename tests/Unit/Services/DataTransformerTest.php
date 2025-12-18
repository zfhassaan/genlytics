<?php

namespace zfhassaan\genlytics\Tests\Unit\Services;

use Google\Analytics\Data\V1beta\DimensionHeader;
use Google\Analytics\Data\V1beta\DimensionValue;
use Google\Analytics\Data\V1beta\MetricHeader;
use Google\Analytics\Data\V1beta\MetricValue;
use Google\Analytics\Data\V1beta\Row;
use Google\Analytics\Data\V1beta\RunReportResponse;
use Google\Analytics\Data\V1beta\RunRealtimeReportResponse;
use zfhassaan\genlytics\Services\DataTransformer;
use zfhassaan\genlytics\Tests\TestCase;

class DataTransformerTest extends TestCase
{
    protected DataTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new DataTransformer();
    }

    public function test_can_transform_report_response()
    {
        $response = $this->createMockReportResponse();

        $result = $this->transformer->transformReport($response);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('dimensions', $result[0]);
        $this->assertArrayHasKey('metrics', $result[0]);
    }

    public function test_can_transform_realtime_report_response()
    {
        $response = $this->createMockRealtimeReportResponse();

        $result = $this->transformer->transformRealtimeReport($response);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('dimensions', $result[0]);
        $this->assertArrayHasKey('metrics', $result[0]);
    }

    public function test_handles_empty_report_response()
    {
        $response = $this->createMock(RunReportResponse::class);
        $response->method('getRows')->willReturn([]);
        $response->method('getDimensionHeaders')->willReturn([]);
        $response->method('getMetricHeaders')->willReturn([]);

        $result = $this->transformer->transformReport($response);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_to_json_format_includes_metadata()
    {
        $data = [
            ['dimensions' => ['country' => 'US'], 'metrics' => ['activeUsers' => '100']],
        ];

        $result = $this->transformer->toJsonFormat($data, true);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertEquals(1, $result['metadata']['count']);
        $this->assertArrayHasKey('timestamp', $result['metadata']);
    }

    public function test_to_json_format_without_metadata()
    {
        $data = [
            ['dimensions' => ['country' => 'US'], 'metrics' => ['activeUsers' => '100']],
        ];

        $result = $this->transformer->toJsonFormat($data, false);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayNotHasKey('metadata', $result);
    }

    public function test_transforms_multiple_dimensions_and_metrics()
    {
        $response = $this->createMockReportResponse([
            ['country' => 'US', 'city' => 'New York'],
            ['country' => 'CA', 'city' => 'Toronto'],
        ], [
            ['activeUsers' => '100', 'sessions' => '150'],
            ['activeUsers' => '50', 'sessions' => '75'],
        ]);

        $result = $this->transformer->transformReport($response);

        $this->assertCount(2, $result);
        $this->assertCount(2, $result[0]['dimensions']);
        $this->assertCount(2, $result[0]['metrics']);
    }

    protected function createMockReportResponse(
        array $dimensions = [['country' => 'US']],
        array $metrics = [['activeUsers' => '100']]
    ): RunReportResponse {
        $rows = [];
        foreach ($dimensions as $index => $dimensionData) {
            $row = $this->createMock(Row::class);

            $dimensionValues = [];
            foreach ($dimensionData as $key => $value) {
                $dimValue = $this->createMock(DimensionValue::class);
                $dimValue->method('getValue')->willReturn($value);
                $dimensionValues[] = $dimValue;
            }
            $row->method('getDimensionValues')->willReturn($dimensionValues);

            $metricValues = [];
            if (isset($metrics[$index])) {
                foreach ($metrics[$index] as $key => $value) {
                    $metValue = $this->createMock(MetricValue::class);
                    $metValue->method('getValue')->willReturn($value);
                    $metricValues[] = $metValue;
                }
            }
            $row->method('getMetricValues')->willReturn($metricValues);

            $rows[] = $row;
        }

        $dimensionHeaders = [];
        if (!empty($dimensions[0])) {
            foreach (array_keys($dimensions[0]) as $index => $name) {
                $header = $this->createMock(DimensionHeader::class);
                $header->method('getName')->willReturn($name);
                $dimensionHeaders[] = $header;
            }
        }

        $metricHeaders = [];
        if (!empty($metrics[0])) {
            foreach (array_keys($metrics[0]) as $index => $name) {
                $header = $this->createMock(MetricHeader::class);
                $header->method('getName')->willReturn($name);
                $metricHeaders[] = $header;
            }
        }

        $response = $this->createMock(RunReportResponse::class);
        $response->method('getRows')->willReturn($rows);
        $response->method('getDimensionHeaders')->willReturn($dimensionHeaders);
        $response->method('getMetricHeaders')->willReturn($metricHeaders);

        return $response;
    }

    protected function createMockRealtimeReportResponse(): RunRealtimeReportResponse
    {
        $row = $this->createMock(Row::class);

        $dimensionValue = $this->createMock(DimensionValue::class);
        $dimensionValue->method('getValue')->willReturn('US');
        $row->method('getDimensionValues')->willReturn([$dimensionValue]);

        $metricValue = $this->createMock(MetricValue::class);
        $metricValue->method('getValue')->willReturn('50');
        $row->method('getMetricValues')->willReturn([$metricValue]);

        $dimensionHeader = $this->createMock(DimensionHeader::class);
        $dimensionHeader->method('getName')->willReturn('country');

        $metricHeader = $this->createMock(MetricHeader::class);
        $metricHeader->method('getName')->willReturn('activeUsers');

        $response = $this->createMock(RunRealtimeReportResponse::class);
        $response->method('getRows')->willReturn([$row]);
        $response->method('getDimensionHeaders')->willReturn([$dimensionHeader]);
        $response->method('getMetricHeaders')->willReturn([$metricHeader]);

        return $response;
    }
}
