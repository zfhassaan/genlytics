# Migration Guide - Genlytics v2.0

## Overview

Genlytics has been completely rewritten implementing Repository pattern, Events, and optimized caching with background processing.

## Breaking Changes

### 1. Service Provider Registration

The service provider now uses dependency injection. The old typo `genltyics` has been fixed to `genlytics`.

**Before:**
```php
$this->app->singleton('genltyics', function () {
    return new Genlytics;
});
```

**After:**
```php
// Automatically handled by service provider
// Uses dependency injection with interfaces
```

### 2. Method Signatures

All methods now support additional options and force refresh:

**Before:**
```php
$analytics->runReports($period, $dimension, $metrics);
```

**After:**
```php
$analytics->runReports($period, $dimensions, $metrics, $options = [], $forceRefresh = false);
```

### 3. Response Format

The response format has been enhanced with metadata:

**Before:**
```json
[
  {"dimension": "value", "metric": "value"}
]
```

**After:**
```json
{
  "data": [
    {
      "dimensions": {"browser": "Chrome"},
      "metrics": {"activeUsers": "100"}
    }
  ],
  "metadata": {
    "count": 1,
    "timestamp": "2024-01-01T00:00:00Z"
  }
}
```

## New Features

### 1. Caching

Analytics data is now automatically cached:

```php
// Cache is enabled by default
// Configure in config/analytics.php
'enable_cache' => true,
'cache_lifetime_in_minutes' => 1440,
```

### 2. Background Processing

Queries can be processed in the background:

```php
// Enable in config
'use_background_jobs' => true,

// Force synchronous processing
$analytics->runReports($period, $dimensions, $metrics, [], true);
```

### 3. Events

Listen to analytics events:

```php
use zfhassaan\genlytics\Events\AnalyticsDataFetched;

Event::listen(AnalyticsDataFetched::class, function ($event) {
    // Handle fetched data
    Log::info('Analytics data fetched', [
        'type' => $event->reportType,
        'from_cache' => $event->fromCache,
    ]);
});
```

### 4. Real-Time Updates

Real-time data is automatically refreshed:

```php
// Enable in config
'enable_realtime_updates' => true,
'realtime_cache_lifetime' => 30,
```

### 5. Repository Pattern

Access the repository directly:

```php
use zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface;

$repository = app(AnalyticsRepositoryInterface::class);
$response = $repository->runReport($dateRange, $dimensions, $metrics);
```

## Configuration

New configuration options in `config/analytics.php`:

```php
'enable_cache' => true,
'use_background_jobs' => true,
'enable_realtime_updates' => true,
'realtime_cache_lifetime' => 30,
'queue_connection' => null,
'queue_name' => 'default',
```

## Environment Variables

Add to your `.env`:

```env
GENLYTICS_ENABLE_CACHE=true
GENLYTICS_USE_BACKGROUND_JOBS=true
GENLYTICS_ENABLE_REALTIME_UPDATES=true
GENLYTICS_REALTIME_CACHE_LIFETIME=30
GENLYTICS_QUEUE_CONNECTION=redis
GENLYTICS_QUEUE_NAME=analytics
```

## Commands

New artisan command:

```bash
# Refresh analytics cache
php artisan genlytics:refresh-cache

# Clear all cache
php artisan genlytics:refresh-cache --clear

# Refresh specific types
php artisan genlytics:refresh-cache --type=report --type=realtime
```

## Queue Setup

For background processing, ensure your queue is configured:

```bash
php artisan queue:work
```

Or use a process manager like Supervisor.

## Backward Compatibility

The main `Genlytics` class maintains backward compatibility. Existing code should work without changes, but you'll benefit from:

- Automatic caching
- Background processing
- Better error handling
- Enhanced response format

## Performance Improvements

1. **Caching**: Reduces API calls significantly
2. **Background Jobs**: Non-blocking requests
3. **Optimized Queries**: Better parameter handling
4. **Real-Time Updates**: Automatic refresh scheduling

## Troubleshooting

### Cache Not Working

Ensure cache is enabled and Laravel cache is configured:

```php
// Check cache driver
CACHE_DRIVER=redis
```

### Background Jobs Not Running

Ensure queue worker is running:

```bash
php artisan queue:work
```

### Real-Time Updates Not Working

Check that:
1. `enable_realtime_updates` is true
2. Queue worker is running
3. Event listeners are registered

