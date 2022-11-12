<?php

namespace zfhassaan\genlytics;

use Google\ApiCore\ApiException;
use Google\Service\ChromeUXReport\Date;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Auth\CredentialsLoader;
use Google\Service\AnalyticsReporting\DimensionFilter;

class Genlytics
{
    /**
     * @param array $period
     * @param array $dimension
     * @param array $metrics
     * @return JsonResponse
     * @throws ApiException
     */
    public function runReports(Array $period, Array $dimension, Array $metrics ): JsonResponse
    {
        $client = new BetaAnalyticsDataClient();
        $property_id = env('PROPERTY_ID');

        $response = $client->runReport([
            'property' => 'properties/' . $property_id,
            'dateRanges' => [
                new DateRange([
                    'start_date' => '2022-10-11',
                    'end_date' => 'today',
                ]),
            ],
            'dimensions' => [new Dimension(
                [
                    'name' => 'eventName'
                ]
            ),
            ],
            'metrics' => [new Metric(
                [
                    'name' => 'eventCount',
                ]
            )
            ]
        ]);

        $result = [];
        foreach ($response->getRows() as $row) {
            $result[] = ['dimension' => $row->getDImensionValues()[0]->getValue(), 'metricsValue' => $row->getMetricValues()[0]->getValue()];
        }

        return response()->json($result);
    }
}
