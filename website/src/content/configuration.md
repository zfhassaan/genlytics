# Configuration & Performance Tuning

Genlytics provides enterprise-grade configuration options for high-throughput applications, allowing complete control over cache drivers, background queues, TTLs, and network transports.

---

## Configuration Reference (`config/analytics.php`)

```php
return [

    // Numeric GA4 Property ID
    'property_id' => env('GENLYTICS_PROPERTY_ID'),

    // Path to Service Account JSON or credentials array
    'service_account_credentials_json' => env('GENLYTICS_CREDENTIALS'),

    // Network transport ('rest' or 'grpc')
    // If gRPC extension is installed, 'grpc' can yield up to 5x-10x performance gains
    'transport' => env('GENLYTICS_TRANSPORT', 'rest'),

    // Cache Configuration
    'cache' => [
        'enabled' => env('GENLYTICS_ENABLE_CACHE', true),

        // Dedicated store ('redis', 'memcached', or null for default)
        'store' => env('GENLYTICS_CACHE_STORE', null),

        // Cache key prefix to prevent collision
        'prefix' => env('GENLYTICS_CACHE_PREFIX', 'genlytics'),

        // Cache tags (supported on Redis and Memcached)
        'use_tags' => env('GENLYTICS_CACHE_USE_TAGS', true),

        // Global default cache TTL in seconds (fallback for all query types)
        'default_ttl' => env('GENLYTICS_CACHE_TTL', 86400), // 24 hours

        // Granular TTLs in seconds per query type
        'ttl' => [
            'report'    => env('GENLYTICS_CACHE_TTL_REPORT', 86400),    // 24 hours
            'realtime'  => env('GENLYTICS_CACHE_TTL_REALTIME', 60),     // 1 minute
            'dimension' => env('GENLYTICS_CACHE_TTL_DIMENSION', 86400), // 24 hours
            'batch'     => env('GENLYTICS_CACHE_TTL_BATCH', 86400),     // 24 hours
            'funnel'    => env('GENLYTICS_CACHE_TTL_FUNNEL', 43200),    // 12 hours
            'ecommerce' => env('GENLYTICS_CACHE_TTL_ECOMMERCE', 43200), // 12 hours
        ],

        // Distributed lock duration to prevent queue stampedes
        'lock_duration' => env('GENLYTICS_CACHE_LOCK_DURATION', 60),
    ],

    // Queue & Background Refresh
    'queue' => [
        'enabled' => env('GENLYTICS_USE_BACKGROUND_JOBS', false),
        'connection' => env('GENLYTICS_QUEUE_CONNECTION', null),
        'name' => env('GENLYTICS_QUEUE_NAME', 'analytics'),
        'unique_for' => env('GENLYTICS_JOB_UNIQUE_FOR', 120),
    ],
];
```

---

## Flexible Cache Expiry (TTL) Configuration

Genlytics gives you 4 layers of cache expiry configuration, from environment variables down to individual queries.

### 1. Environment & Config Defaults
Configure system-wide or per-type expiry directly in your `.env` file:

```env
# Global fallback TTL (e.g. 12 hours = 43200 seconds)
GENLYTICS_CACHE_TTL=43200

# Granular TTLs per operation type
GENLYTICS_CACHE_TTL_REPORT=86400
GENLYTICS_CACHE_TTL_REALTIME=30
GENLYTICS_CACHE_TTL_DIMENSION=86400
GENLYTICS_CACHE_TTL_BATCH=86400
GENLYTICS_CACHE_TTL_FUNNEL=43200
GENLYTICS_CACHE_TTL_ECOMMERCE=43200
```

### 2. Runtime Configuration via Facade & Service
Modify cache lifetimes dynamically during the application lifecycle or in scheduled commands:

```php
use zfhassaan\genlytics\facades\AnalyticsFacade as Genlytics;

// Update TTL for a specific query category (seconds)
Genlytics::setCacheTtl(1800, 'report');
Genlytics::setCacheTtl(30, 'realtime');

// Inspect current TTL
$reportTtl = Genlytics::getCacheTtl('report'); // 1800

// Direct CacheManager access
Genlytics::cache()->setDefaultLifetime(3600);
Genlytics::cache()->setLifetime(7200, 'funnel');
$lifetimes = Genlytics::cache()->getLifetimes();
```

### 3. Query-Level Cache Expiry (`remember`, `cacheFor`, `ttl`)
Override the cache duration for a specific query using Eloquent-style fluent methods. Supports integer seconds, `\DateInterval`, or `\DateTimeInterface`:

```php
// Integer seconds (2 hours)
$data = Genlytics::query()
    ->last7Days()
    ->dimensions(['country', 'city'])
    ->metrics(['activeUsers', 'sessions'])
    ->remember(7200)
    ->get();

// DateInterval (e.g., 30 minutes)
$data = Genlytics::query()
    ->today()
    ->dimension('pagePath')
    ->metric('screenPageViews')
    ->cacheFor(new \DateInterval('PT30M'))
    ->get();

// DateTimeInterface (until midnight tonight)
$data = Genlytics::query()
    ->dimension('deviceCategory')
    ->metric('activeUsers')
    ->ttl(now()->endOfDay())
    ->get();

// Bypass cache entirely (force fresh query)
$fresh = Genlytics::query()
    ->last7Days()
    ->dimension('country')
    ->metric('activeUsers')
    ->dontCache() // or ->noCache()
    ->get();
```

### 4. Dedicated Helper Custom TTL
All dedicated helpers accept an optional `$ttl` parameter:

```php
// Cache top products for 30 minutes (1800s)
$products = Genlytics::topProducts(10, '30daysAgo', 1800);

// Cache categories for 1 hour
$categories = Genlytics::topCategories(10, '30daysAgo', 3600);

// Cache e-commerce purchase funnel for 6 hours
$funnel = Genlytics::ecommerceFunnel('30daysAgo', 21600);
```

---

## High-Scalability Caching: Stale-While-Revalidate

Under high traffic (e.g., hundreds of concurrent users loading an analytics dashboard), traditional caching often suffers from the **Thundering Herd** or **Cache Stampede** problem:
1. When cache expires, 100 requests hit the API simultaneously.
2. Google Analytics 4 API rate limits trigger (`429 ResourceExhausted`).

Genlytics solves this using **Stale-While-Revalidate (SWR)** with **Atomic Distributed Locks**:

```
Request arrives ────────► Cache Hit? 
                             │
            ┌────────────────┴────────────────┐
            ▼ Yes                             ▼ No
    Return cached data           Synchronously fetch from GA4
            │                    Populate cache & return
    [Is Background Job Enabled?]
            │
            ▼
    Acquire Atomic Lock (60s)?
            │
      ┌─────┴──────┐
      ▼ Yes        ▼ No (Lock already held by another worker)
 Dispatch 1 Job    Do nothing (Prevent queue bombardment)
 to Queue
```

---

## Cache Management Artisan Command

Genlytics comes with an Artisan command to clear or inspect your analytics cache:

```bash
# Clear all Genlytics cache instantly (uses Redis tags if available):
php artisan genlytics:refresh-cache --clear

# Refresh specific query policies:
php artisan genlytics:refresh-cache --type=report
php artisan genlytics:refresh-cache --type=realtime
```
