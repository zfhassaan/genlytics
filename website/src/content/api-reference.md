# API Reference

Genlytics provides two parallel access styles:
1. **Raw Data Methods (`getReport`, `getRealTime`, etc.)**: Returns pure PHP `array` datasets. Recommended for internal services, queued jobs, console commands, and data pipelines.
2. **HTTP Methods (`runReports`, `runRealTime`, etc.)**: Returns `Illuminate\Http\JsonResponse` objects. Ideal for direct controller returns.

---

## 1. Standard Reports

### `Genlytics::getReport(...)` / `Genlytics::runReports(...)`

Queries GA4 for historical analytics with dimensions, metrics, date ranges, and advanced filters.

#### Signature:
```php
public static function getReport(
    array $period,
    array|string $dimension,
    array|string $metrics,
    array $options = [],
    bool $forceRefresh = false
): array
```

#### Example:
```php
use zfhassaan\genlytics\facades\AnalyticsFacade as Genlytics;

$report = Genlytics::getReport(
    period: ['start_date' => '30daysAgo', 'end_date' => 'today'],
    dimension: ['country', 'city'],
    metrics: ['activeUsers', 'sessions', 'screenPageViews'],
    options: [
        'limit' => 10,
    ]
);
```

#### Return Structure:
```json
[
  {
    "dimensions": {
      "country": "United States",
      "city": "New York"
    },
    "metrics": {
      "activeUsers": "4520",
      "sessions": "6100",
      "screenPageViews": "18400"
    }
  }
]
```

---

## 2. Real-Time Reports

### `Genlytics::getRealTime(...)` / `Genlytics::runRealTime(...)`

Fetches active user metrics from the last 30 minutes in real time.

#### Signature:
```php
public static function getRealTime(
    array|string $dimension,
    array|string $metrics,
    array $options = [],
    bool $forceRefresh = false
): array
```

#### Example:
```php
$realtime = Genlytics::getRealTime(
    dimension: 'country',
    metrics: 'activeUsers',
    options: ['limit' => 5]
);
```

---

## 3. Dimension-Only Reports

### `Genlytics::getDimensionReport(...)` / `Genlytics::runDimensionReport(...)`

Runs a lightweight query focusing solely on dimension values over a date range.

#### Signature:
```php
public static function getDimensionReport(
    array $period,
    string|array $dimension,
    array $options = [],
    bool $forceRefresh = false
): array
```

#### Example:
```php
$dimensions = Genlytics::getDimensionReport(
    period: ['start_date' => '7daysAgo', 'end_date' => 'today'],
    dimension: 'pagePath',
    options: ['limit' => 20]
);
```

---

## 4. Batch Reports (Multi-Report API)

### `Genlytics::getBatchReports(...)` / `Genlytics::batchRunReports(...)`

Executes multiple distinct reports in **a single Google API HTTP request**. Essential for loading multi-widget dashboards.

#### Signature:
```php
public static function getBatchReports(
    array $reports,
    bool $forceRefresh = false
): array
```

#### Example:
```php
$batch = Genlytics::getBatchReports([
    'top_pages' => [
        'dateRange'  => ['start_date' => '7daysAgo', 'end_date' => 'today'],
        'dimensions' => ['pagePath'],
        'metrics'    => ['screenPageViews'],
        'options'    => ['limit' => 5],
    ],
    'top_countries' => [
        'dateRange'  => ['start_date' => '7daysAgo', 'end_date' => 'today'],
        'dimensions' => ['country'],
        'metrics'    => ['activeUsers'],
        'options'    => ['limit' => 5],
    ],
]);

// $batch['top_pages'] => array of top pages
// $batch['top_countries'] => array of top countries
```

---

## 5. Cache Lifecycle & Runtime TTL

### `Genlytics::setCacheTtl(...)`
Configures cache lifetime dynamically in seconds for a specific query type (`report`, `realtime`, `dimension`, `batch`, `funnel`, `ecommerce`) or globally if type is null.

```php
Genlytics::setCacheTtl(1800, 'report'); // 30 minutes for standard reports
Genlytics::setCacheTtl(30, 'realtime');  // 30 seconds for realtime
```

### `Genlytics::getCacheTtl(...)`
Returns current configured TTL in seconds.

```php
$ttl = Genlytics::getCacheTtl('report'); // 1800
```

### `Genlytics::cache()`
Returns the underlying `CacheManagerInterface` instance.

```php
$cache = Genlytics::cache();
$cache->setDefaultLifetime(3600);
$cache->setLifetime(7200, 'funnel');
$allTtls = $cache->getLifetimes();
```

