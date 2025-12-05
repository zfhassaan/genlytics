# Usage Guide

This guide explains how to use Genlytics in your Laravel application.

## Basic Usage

### Getting Started

```php
use zfhassaan\genlytics\Genlytics;

$analytics = new Genlytics();
```

Or use the facade:

```php
use Genlytics;

$result = Genlytics::runReports(...);
```

Or dependency injection:

```php
use zfhassaan\genlytics\Genlytics;

class AnalyticsController extends Controller
{
    public function __construct(
        protected Genlytics $analytics
    ) {}
}
```

## Running Reports

### Basic Report

```php
$period = [
    'start_date' => '7daysAgo',
    'end_date' => 'today'
];

$dimensions = [['name' => 'country']];
$metrics = [['name' => 'activeUsers']];

$result = $analytics->runReports($period, $dimensions, $metrics);
```

### Multiple Dimensions and Metrics

```php
$dimensions = [
    ['name' => 'country'],
    ['name' => 'city'],
    ['name' => 'deviceCategory'],
];

$metrics = [
    ['name' => 'activeUsers'],
    ['name' => 'sessions'],
    ['name' => 'bounceRate'],
];

$result = $analytics->runReports($period, $dimensions, $metrics);
```

### With Options

```php
$options = [
    'limit' => 100,
    'offset' => 0,
    'orderBys' => [
        [
            'metric' => [
                'metricName' => 'activeUsers'
            ],
            'desc' => true
        ]
    ]
];

$result = $analytics->runReports($period, $dimensions, $metrics, $options);
```

### Force Refresh (Bypass Cache)

```php
$result = $analytics->runReports(
    $period,
    $dimensions,
    $metrics,
    [],
    true // Force refresh
);
```

## Real-Time Reports

### Basic Real-Time Report

```php
$dimensions = [['name' => 'country']];
$metrics = [['name' => 'activeUsers']];

$result = $analytics->runRealTime($dimensions, $metrics);
```

### Real-Time with Multiple Dimensions

```php
$dimensions = [
    ['name' => 'country'],
    ['name' => 'deviceCategory'],
];

$metrics = [
    ['name' => 'activeUsers'],
    ['name' => 'screenPageViews'],
];

$result = $analytics->runRealTime($dimensions, $metrics);
```

## Dimension Reports

### Single Dimension

```php
$period = [
    'start_date' => '30daysAgo',
    'end_date' => 'today'
];

$result = $analytics->runDimensionReport($period, 'country');
```

### Multiple Dimensions

```php
$dimensions = ['country', 'city'];
$result = $analytics->runDimensionReport($period, $dimensions);
```

## Response Format

### Standard Response

```json
{
    "data": [
        {
            "dimensions": {
                "country": "United States",
                "city": "New York"
            },
            "metrics": {
                "activeUsers": "1250",
                "sessions": "1500"
            }
        }
    ],
    "metadata": {
        "count": 1,
        "timestamp": "2024-01-01T00:00:00Z"
    }
}
```

### Handling Response

```php
$result = $analytics->runReports($period, $dimensions, $metrics);
$data = $result->getData(true); // Get as array

foreach ($data['data'] as $row) {
    $country = $row['dimensions']['country'];
    $users = $row['metrics']['activeUsers'];
    
    // Process data
}
```

## Using Events

### Listen to Data Fetched Event

```php
use zfhassaan\genlytics\Events\AnalyticsDataFetched;
use Illuminate\Support\Facades\Event;

Event::listen(AnalyticsDataFetched::class, function ($event) {
    Log::info('Analytics data fetched', [
        'type' => $event->reportType,
        'from_cache' => $event->fromCache,
        'data_count' => count($event->data),
    ]);
});
```

### Listen to Cache Updates

```php
use zfhassaan\genlytics\Events\AnalyticsCacheUpdated;

Event::listen(AnalyticsCacheUpdated::class, function ($event) {
    // Notify users of updated data
    broadcast(new AnalyticsUpdated($event->data));
});
```

### Handle Query Failures

```php
use zfhassaan\genlytics\Events\AnalyticsQueryFailed;

Event::listen(AnalyticsQueryFailed::class, function ($event) {
    Log::error('Analytics query failed', [
        'type' => $event->reportType,
        'error' => $event->exception->getMessage(),
    ]);
    
    // Send alert
    Mail::to('admin@example.com')->send(new AnalyticsErrorAlert($event));
});
```

## Using Repository Directly

For advanced usage, access the repository directly:

```php
use zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface;

$repository = app(AnalyticsRepositoryInterface::class);

$response = $repository->runReport(
    $dateRange,
    $dimensions,
    $metrics,
    $options
);

// Work with raw Google Analytics response
$rows = $response->getRows();
```

## Using Services Directly

Access the analytics service for more control:

```php
use zfhassaan\genlytics\Services\AnalyticsService;

$service = app(AnalyticsService::class);

$result = $service->runReports(
    $dateRange,
    $dimensions,
    $metrics,
    $options,
    $forceRefresh
);
```

## Common Use Cases

### Dashboard Widget

```php
class DashboardController extends Controller
{
    public function analytics(Genlytics $analytics)
    {
        $period = [
            'start_date' => '30daysAgo',
            'end_date' => 'today'
        ];
        
        $metrics = [['name' => 'activeUsers']];
        
        $users = $analytics->runReports($period, [], $metrics);
        
        return view('dashboard.analytics', [
            'users' => $users->getData(true)
        ]);
    }
}
```

### Real-Time Visitor Count

```php
public function liveVisitors(Genlytics $analytics)
{
    $metrics = [['name' => 'activeUsers']];
    $result = $analytics->runRealTime([], $metrics);
    
    return response()->json([
        'visitors' => $result->getData(true)['data'][0]['metrics']['activeUsers'] ?? 0
    ]);
}
```

### Top Countries Report

```php
public function topCountries(Genlytics $analytics)
{
    $period = ['start_date' => '30daysAgo', 'end_date' => 'today'];
    $dimensions = [['name' => 'country']];
    $metrics = [['name' => 'activeUsers']];
    
    $options = [
        'orderBys' => [
            [
                'metric' => ['metricName' => 'activeUsers'],
                'desc' => true
            ]
        ],
        'limit' => 10
    ];
    
    $result = $analytics->runReports($period, $dimensions, $metrics, $options);
    
    return $result->getData(true);
}
```

### Device Breakdown

```php
public function deviceBreakdown(Genlytics $analytics)
{
    $period = ['start_date' => '7daysAgo', 'end_date' => 'today'];
    $dimensions = [['name' => 'deviceCategory']];
    $metrics = [
        ['name' => 'activeUsers'],
        ['name' => 'sessions']
    ];
    
    return $analytics->runReports($period, $dimensions, $metrics);
}
```

## Cache Management

### Clear Cache

```bash
php artisan genlytics:refresh-cache --clear
```

### Refresh Specific Cache

```bash
php artisan genlytics:refresh-cache --type=report
php artisan genlytics:refresh-cache --type=realtime
```

### Programmatic Cache Management

```php
use zfhassaan\genlytics\Contracts\CacheManagerInterface;

$cacheManager = app(CacheManagerInterface::class);

// Clear specific cache
$cacheManager->forget('report:abc123');

// Clear all cache
$cacheManager->clear();
```

## Error Handling

### Try-Catch

```php
try {
    $result = $analytics->runReports($period, $dimensions, $metrics);
} catch (\Exception $e) {
    Log::error('Analytics error', [
        'error' => $e->getMessage(),
    ]);
    
    return response()->json([
        'error' => 'Failed to fetch analytics data'
    ], 500);
}
```

### Check Response

```php
$result = $analytics->runReports($period, $dimensions, $metrics);
$data = $result->getData(true);

if (empty($data['data'])) {
    // No data available
    return response()->json(['message' => 'No data found'], 404);
}
```

## Best Practices

1. **Use Caching**: Always enable cache in production
2. **Background Jobs**: Use background jobs for better performance
3. **Error Handling**: Always wrap in try-catch
4. **Event Listeners**: Use events for logging and monitoring
5. **Cache Refresh**: Use force refresh sparingly
6. **Real-Time Data**: Use real-time only when necessary (higher API usage)

## Next Steps

- [[Configuration-Guide|Configuration Guide]]
- [[Troubleshooting|Troubleshooting Guide]]
- [[Contributing|Contributing Guide]]

