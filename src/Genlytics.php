<?php

namespace zfhassaan\genlytics;

use Google\ApiCore\ApiException;
use Google\Service\ChromeUXReport\Date;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use zfhassaan\genlytics\overrides\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Auth\Credentialsloader;
use Google\Service\AnalyticsReporting\DimensionFilter;
use Throwable;

class Genlytics
{
    protected string $property_id;
    protected mixed $client;

    /**
     * Default Constructor for Genlytics
     */
    public function __construct()
    {
        $this->initConfig();
    }

    /**
     * Initial Configuration
     */
    public function initConfig(): void
    {
        $this->property_id = 'properties/' . config('analytics.property_id');
        $this->client = new BetaAnalyticsDataClient();
    }


    /**
     * Run Report for any Dimension and Metrics. This can fetch data with respective to date and also with respective
     * of the metrics provided. i.e. Demographics, Medium etc.
     *
     * @param array $period
     * @param array $dimension
     * @param array $metrics
     * @return JsonResponse
     * @throws ApiException
     */
    public function runReports(array $period, array $dimension, array $metrics): JsonResponse
    {

        try {
            $options = [
                'property' => $this->property_id,
                'dateRanges' => [new DateRange([
                    'start_date' => $period['start_date'],
                    'end_date' => $period['end_date']
                ])],
                'dimensions' => [new Dimension($dimension)],
                'metrics' => [new Metric($metrics)]
            ];

            $response = $this->client->runReport($options);
            return $this->returnJson($response);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get Real Time Data from Analytics
     *
     * @param array $dimension
     * @param array $metrics
     * @return JsonResponse
     */
    public function runRealTime(array $dimension, array $metrics): JsonResponse
    {
        try {
            $response = $this->client->runRealtimeReport([
                'property' => $this->property_id,
                'dimensions' => [new Dimension($dimension)],
                'metrics' => [new Metric($metrics)]
            ]);
            return $this->returnJson($response);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'error' => $e->getMessage()], 400);
        }
    }


    /**
     * Run only Dimensions for Analytics with Time Duration
     *
     * @param array $period
     * @param $dimension
     * @return mixed
     */
    public function RunDimensionReport(array $period, $dimension): mixed
    {
        try {
            return $this->client->runReport([
                'property' => $this->property_id,
                'dateRanges' => [new DateRange($period)],
                'dimensions' => [new Dimension($dimension)]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * Format the response from Analytics to JSON format.
     *
     * @param $response
     * @return JsonResponse
     */
    protected function returnJson($response): JsonResponse
    {
        try {
            $result = [];
            foreach ($response->getRows() as $row) {
                $result[] = ['dimension' => $row->getDImensionValues()[0]->getValue(), 'metric' => $row->getMetricValues()[0]->getValue()];
            }
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'error' => $e->getMessage()], 400);
        }
    }
}
