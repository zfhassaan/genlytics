# Configuration Guide

This guide explains all configuration options available in Genlytics.

## Configuration File

After publishing the configuration, you'll find it at `config/analytics.php`.

## Basic Configuration

### Property ID

```php
'property_id' => env('GENLYTICS_PROPERTY_ID'),
```

Your Google Analytics 4 property ID (numeric only).

**Example**:
```env
GENLYTICS_PROPERTY_ID=123456789
```

### Service Account Credentials

```php
'service_account_credentials_json' => env('GENLYTICS_CREDENTIALS'),
```

Path to your service account JSON file or the JSON content itself.

**Option 1: File Path**
```env
GENLYTICS_CREDENTIALS=storage/app/analytics/service-account.json
```

**Option 2: JSON Content**
```env
GENLYTICS_CREDENTIALS={"type":"service_account","project_id":"..."}
```

## Cache Configuration

### Enable Cache

```php
'enable_cache' => env('GENLYTICS_ENABLE_CACHE', true),
```

Enable or disable caching of analytics data.

**Default**: `true`

**Benefits**:
- Reduces API calls by 90%+
- Faster response times
- Lower API quota usage

### Cache Lifetime

```php
'cache_lifetime_in_minutes' => env('GENLYTICS_CACHE_LIFETIME', 60 * 24),
```

How long to cache analytics data (in minutes).

**Default**: `1440` (24 hours)

**Recommendations**:
- **High-traffic sites**: 60-120 minutes
- **Low-traffic sites**: 1440 minutes (24 hours)
- **Real-time dashboards**: Disable cache or use 5-15 minutes

**Example**:
```env
GENLYTICS_CACHE_LIFETIME=60
```

### Cache Store

```php
'cache' => [
    'store' => env('GENLYTICS_CACHE_STORE', 'file'),
],
```

Laravel cache store to use.

**Options**:
- `file`: File-based cache (default)
- `redis`: Redis cache (recommended for production)
- `database`: Database cache
- `memcached`: Memcached cache

**Example**:
```env
GENLYTICS_CACHE_STORE=redis
```

## Background Jobs Configuration

### Enable Background Jobs

```php
'use_background_jobs' => env('GENLYTICS_USE_BACKGROUND_JOBS', true),
```

Enable background processing for analytics queries.

**Default**: `true`

**Benefits**:
- Non-blocking requests
- Better user experience
- Automatic cache refresh

**Requirements**:
- Queue worker must be running
- Queue connection configured

### Queue Connection

```php
'queue_connection' => env('GENLYTICS_QUEUE_CONNECTION', null),
```

Custom queue connection for analytics jobs.

**Default**: Uses Laravel's default queue connection

**Example**:
```env
GENLYTICS_QUEUE_CONNECTION=redis
```

### Queue Name

```php
'queue_name' => env('GENLYTICS_QUEUE_NAME', 'default'),
```

Queue name for analytics jobs.

**Default**: `default`

**Example**:
```env
GENLYTICS_QUEUE_NAME=analytics
```

## Real-Time Updates Configuration

### Enable Real-Time Updates

```php
'enable_realtime_updates' => env('GENLYTICS_ENABLE_REALTIME_UPDATES', true),
```

Enable automatic real-time data refresh.

**Default**: `true`

**How it works**:
- Real-time data is cached for a short duration
- Background jobs automatically refresh the cache
- Ensures data is always fresh

### Real-Time Cache Lifetime

```php
'realtime_cache_lifetime' => env('GENLYTICS_REALTIME_CACHE_LIFETIME', 30),
```

Cache lifetime for real-time data (in seconds).

**Default**: `30` seconds

**Recommendations**:
- **Live dashboards**: 15-30 seconds
- **Standard use**: 30-60 seconds
- **API quota concerns**: 60-120 seconds

**Example**:
```env
GENLYTICS_REALTIME_CACHE_LIFETIME=15
```

## Complete Configuration Example

```php
return [
    'property_id' => env('GENLYTICS_PROPERTY_ID'),
    'service_account_credentials_json' => env('GENLYTICS_CREDENTIALS'),
    
    // Cache
    'cache_lifetime_in_minutes' => env('GENLYTICS_CACHE_LIFETIME', 1440),
    'enable_cache' => env('GENLYTICS_ENABLE_CACHE', true),
    'cache' => [
        'store' => env('GENLYTICS_CACHE_STORE', 'file'),
    ],
    
    // Background Jobs
    'use_background_jobs' => env('GENLYTICS_USE_BACKGROUND_JOBS', true),
    'queue_connection' => env('GENLYTICS_QUEUE_CONNECTION', null),
    'queue_name' => env('GENLYTICS_QUEUE_NAME', 'default'),
    
    // Real-Time Updates
    'enable_realtime_updates' => env('GENLYTICS_ENABLE_REALTIME_UPDATES', true),
    'realtime_cache_lifetime' => env('GENLYTICS_REALTIME_CACHE_LIFETIME', 30),
];
```

## Environment Variables Summary

```env
# Required
GENLYTICS_PROPERTY_ID=123456789
GENLYTICS_CREDENTIALS=storage/app/analytics/service-account.json

# Optional - Cache
GENLYTICS_ENABLE_CACHE=true
GENLYTICS_CACHE_LIFETIME=1440
GENLYTICS_CACHE_STORE=redis

# Optional - Background Jobs
GENLYTICS_USE_BACKGROUND_JOBS=true
GENLYTICS_QUEUE_CONNECTION=redis
GENLYTICS_QUEUE_NAME=analytics

# Optional - Real-Time
GENLYTICS_ENABLE_REALTIME_UPDATES=true
GENLYTICS_REALTIME_CACHE_LIFETIME=30
```

## Configuration Scenarios

### Scenario 1: Development Environment

```env
GENLYTICS_ENABLE_CACHE=false
GENLYTICS_USE_BACKGROUND_JOBS=false
```

**Why**: Always get fresh data, no queue setup needed.

### Scenario 2: Production with High Traffic

```env
GENLYTICS_ENABLE_CACHE=true
GENLYTICS_CACHE_LIFETIME=60
GENLYTICS_CACHE_STORE=redis
GENLYTICS_USE_BACKGROUND_JOBS=true
GENLYTICS_QUEUE_CONNECTION=redis
```

**Why**: Optimize performance, reduce API calls.

### Scenario 3: Real-Time Dashboard

```env
GENLYTICS_ENABLE_CACHE=true
GENLYTICS_CACHE_LIFETIME=5
GENLYTICS_ENABLE_REALTIME_UPDATES=true
GENLYTICS_REALTIME_CACHE_LIFETIME=15
GENLYTICS_USE_BACKGROUND_JOBS=true
```

**Why**: Balance freshness with API quota.

### Scenario 4: API Quota Limited

```env
GENLYTICS_ENABLE_CACHE=true
GENLYTICS_CACHE_LIFETIME=1440
GENLYTICS_USE_BACKGROUND_JOBS=true
GENLYTICS_ENABLE_REALTIME_UPDATES=false
```

**Why**: Maximize cache usage, minimize API calls.

## Advanced Configuration

### Custom Cache Manager

You can implement a custom cache manager:

```php
use zfhassaan\genlytics\Contracts\CacheManagerInterface;

class CustomCacheManager implements CacheManagerInterface
{
    // Implementation
}
```

Register in `AppServiceProvider`:

```php
$this->app->bind(
    CacheManagerInterface::class,
    CustomCacheManager::class
);
```

### Custom Repository

Implement a custom repository:

```php
use zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface;

class CustomAnalyticsRepository implements AnalyticsRepositoryInterface
{
    // Implementation
}
```

Register in `AppServiceProvider`:

```php
$this->app->bind(
    AnalyticsRepositoryInterface::class,
    CustomAnalyticsRepository::class
);
```

## Validation

Genlytics validates configuration on service provider boot. Common issues:

- Missing `property_id`: Throws exception
- Invalid credentials path: Throws exception
- Invalid cache store: Falls back to default

## Next Steps

- [[Usage-Guide|Learn how to use Genlytics]]
- [[Troubleshooting|Troubleshooting Guide]]

