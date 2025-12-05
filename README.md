<!--suppress ALL -->
<p align="center">
    <img align="center" class="img-fluid" src="banner.jpeg"/>
</p>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/zfhassaan/genlytics.svg?style=flat-square)](https://packagist.org/packages/zfhassaan/genlytics)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/zfhassaan/genlytics.svg?style=flat-square)](https://packagist.org/packages/zfhassaan/genlytics)
[![Hits](https://hits.seeyoufarm.com/api/count/incr/badge.svg?url=https%3A%2F%2Fgithub.com%2Fzfhassaan%2Fgenlytics&count_bg=%2379C83D&title_bg=%23555555&icon=&icon_color=%23E7E7E7&title=hits&edge_flat=false)](https://hits.seeyoufarm.com)

### Disclaimer 
The Google UA Property is going away from July 2023. Google is implementing the Google Analytics V4. 
_Universal Analytics will no longer process new data in standard properties beginning on 1 July 2023. 
Prepare now by setting up and switching over to a Google Analytics 4 property._
[Learn More](https://support.google.com/analytics/answer/11583528?hl=en-GB&authuser=0)

The Google Analytics data will only be available through the GCP API.

## About

Genlytics is a powerful Google Analytics 4 (GA4) package for Laravel, completely rewritten following **SOLID principles** with **Repository pattern**, **Event-driven architecture**, and **performance optimizations**. The package integrates seamlessly with your Laravel application, providing intelligent caching, background job processing, and real-time analytics updates.

### Key Features

- **Repository Pattern** - Abstracted data access layer for easy testing and extension
- **Event-Driven** - Decoupled event system for extensibility
- **Intelligent Caching** - Automatic cache management with configurable TTL (90%+ API call reduction)
- **Background Jobs** - Non-blocking analytics queries for better performance
- **Real-Time Updates** - Automatic refresh scheduling for live dashboards
- **Type Safety** - Full type hints and comprehensive PHPDoc
- **Error Handling** - Robust error handling with logging
- **Comprehensive Tests** - 52 test cases with 83+ assertions
- **Backward Compatible** - Existing code works without changes

### Architecture Highlights

- **Single Responsibility** - Each class has one clear purpose
- **Dependency Inversion** - Dependencies on abstractions, not concretions
- **Interface Segregation** - Focused, specific interfaces
- **Open/Closed** - Extensible via interfaces without modification

## Requirements

- PHP 8.1 or higher
- Laravel 9.0 or higher
- Google Analytics 4 (GA4) property
- Google Cloud Platform project with Analytics Data API enabled
- Service account with Analytics access

## Installation

Install the package via Composer:

```bash
composer require zfhassaan/genlytics
```

### Auto-Discovery

The package supports Laravel's auto-discovery, so the service provider and facade will be automatically registered. No manual configuration needed!

### Manual Registration (Optional)

If you prefer manual registration, add to `config/app.php`:

```php
'providers' => [
    // ...
    zfhassaan\genlytics\provider\AnalyticsServiceProvider::class,
],

'aliases' => [
    // ...
    'Genlytics' => zfhassaan\genlytics\facades\AnalyticsFacade::class,
],
```

## Configuration

### Publish Configuration

```bash
php artisan vendor:publish --tag=genlytics-config
```

Or publish everything:

```bash
php artisan vendor:publish --provider="zfhassaan\genlytics\provider\AnalyticsServiceProvider"
```

### Environment Variables

Add to your `.env` file:

```env
# Required
GENLYTICS_PROPERTY_ID=your-property-id-here
GENLYTICS_CREDENTIALS=storage/app/analytics/service-account.json

# Optional - Cache Configuration
GENLYTICS_ENABLE_CACHE=true
GENLYTICS_CACHE_LIFETIME=1440

# Optional - Background Jobs
GENLYTICS_USE_BACKGROUND_JOBS=true
GENLYTICS_QUEUE_CONNECTION=redis
GENLYTICS_QUEUE_NAME=default

# Optional - Real-Time Updates
GENLYTICS_ENABLE_REALTIME_UPDATES=true
GENLYTICS_REALTIME_CACHE_LIFETIME=30
```

### Google Analytics Setup

1. **Create GA4 Property** - Create a Google Analytics 4 property
2. **Enable Analytics Data API** - Enable in Google Cloud Console
3. **Create Service Account** - Create service account in GCP
4. **Generate JSON Key** - Download service account JSON key
5. **Grant Analytics Access** - Add service account email to GA4 property with Viewer/Analyst role

See [Installation Guide](wiki/Installation-Guide.md) for detailed steps.

## Usage

### Basic Usage

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

### Running Reports

#### Basic Report

```php
$period = [
    'start_date' => '7daysAgo',
    'end_date' => 'today'
];

$dimensions = [['name' => 'country']];
$metrics = [['name' => 'activeUsers']];

$result = $analytics->runReports($period, $dimensions, $metrics);
```

#### Multiple Dimensions and Metrics

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

#### With Options

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

#### Force Refresh (Bypass Cache)

```php
$result = $analytics->runReports(
    $period,
    $dimensions,
    $metrics,
    [],
    true // Force refresh from API
);
```

### Real-Time Reports

```php
$dimensions = [['name' => 'country']];
$metrics = [['name' => 'activeUsers']];

$result = $analytics->runRealTime($dimensions, $metrics);
```

### Dimension Reports

```php
$period = [
    'start_date' => '30daysAgo',
    'end_date' => 'today'
];

// Single dimension
$result = $analytics->runDimensionReport($period, 'country');

// Multiple dimensions
$result = $analytics->runDimensionReport($period, ['country', 'city']);
```

### Response Format

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

## Advanced Features

### Caching

Genlytics automatically caches analytics data to reduce API calls:

```php
// Cache is enabled by default
// Configure in config/analytics.php or .env
GENLYTICS_ENABLE_CACHE=true
GENLYTICS_CACHE_LIFETIME=1440  // 24 hours in minutes
```

**Benefits:**
- 90%+ reduction in API calls
- Faster response times
- Lower API quota usage

### Background Jobs

Process analytics queries in the background:

```php
// Enable in config
GENLYTICS_USE_BACKGROUND_JOBS=true

// Requires queue worker
php artisan queue:work
```

**Benefits:**
- Non-blocking requests
- Better user experience
- Automatic cache refresh

### Real-Time Updates

Automatic real-time data refresh:

```php
// Enable in config
GENLYTICS_ENABLE_REALTIME_UPDATES=true
GENLYTICS_REALTIME_CACHE_LIFETIME=30  // seconds
```

### Events

Listen to analytics events:

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

**Available Events:**
- `AnalyticsDataRequested` - Fired when data is requested
- `AnalyticsDataFetched` - Fired when data is successfully fetched
- `AnalyticsCacheUpdated` - Fired when cache is updated
- `AnalyticsQueryFailed` - Fired when query fails

### Using Repository Directly

For advanced usage, access the repository:

```php
use zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface;

$repository = app(AnalyticsRepositoryInterface::class);

$response = $repository->runReport(
    $dateRange,
    $dimensions,
    $metrics,
    $options
);
```

## Publishing Test Cases

Publish test cases to your application for customization:

```bash
php artisan vendor:publish --tag=genlytics-tests
```

This publishes:
- `tests/Genlytics/TestCase.php` - Base test case
- `tests/Genlytics/Unit/` - Unit tests
- `tests/Genlytics/Feature/` - Feature tests
- `tests/Genlytics/Integration/` - Integration tests

See [PUBLISHING.md](tests/PUBLISHING.md) for details.

## Testing

Run the test suite:

```bash
composer test
```

Or with coverage:

```bash
composer test-coverage
```

**Test Coverage:**
- 52 test cases
- 83+ assertions
- All major components tested
- Mocks for external dependencies (no real API calls needed)

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
    
    return $analytics->runReports($period, $dimensions, $metrics, $options);
}
```

## Error Handling

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

## Performance Optimizations

1. **Intelligent Caching** - Reduces API calls by 90%+
2. **Background Processing** - Non-blocking requests
3. **Query Optimization** - Efficient parameter handling
4. **Real-Time Scheduling** - Automatic refresh management

## Documentation

- [Installation Guide](wiki/Installation-Guide.md) - Step-by-step setup
- [Configuration Guide](wiki/Configuration-Guide.md) - All configuration options
- [Usage Guide](wiki/Usage-Guide.md) - Usage examples and best practices
- [Troubleshooting](wiki/Troubleshooting.md) - Common issues and solutions
- [How to Release](wiki/How-to-Release.md) - Release process
- [Contributing](wiki/Contributing.md) - Contribution guidelines
- [Architecture](ARCHITECTURE.md) - Architecture documentation
- [Migration Guide](MIGRATION.md) - Migration from v1.x to v2.0

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for all changes.

### v2.0.0 - Major Rewrite

- Complete rewrite following SOLID principles
- Repository pattern implementation
- Event-driven architecture
- Intelligent caching system
- Background job processing
- Real-time updates
- Comprehensive test suite
- Enhanced error handling
- Full backward compatibility

## Support

- [GitHub Issues](https://github.com/zfhassaan/genlytics/issues)
- [GitHub Wiki](https://github.com/zfhassaan/genlytics.wiki)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Author

- **Author**: Hassaan Ali
- **Email**: zfhassaan@gmail.com

---

**Made with ❤️ for the Laravel community**
