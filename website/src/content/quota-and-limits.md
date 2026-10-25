# Google Analytics 4 (GA4) API Limits & Maximum Utilization

Genlytics features an enterprise-grade **Quota & Adaptive Resource Pacing Engine** built specifically for the Google Analytics Data API (GA4) quota specifications.

It protects your application from `429 Too Many Requests` or `RESOURCE_EXHAUSTED` errors while unlocking **maximum utilization of your daily API budget**.

---

## GA4 Data API Quota Specifications

Genlytics enforces and tracks all official GA4 Data API quota limits:

| Quota Dimension | Standard (Free) Property | Analytics 360 Property | Reset Window |
| :--- | :--- | :--- | :--- |
| **Core Tokens Per Day** | **200,000 tokens** | **2,000,000 tokens** | Midnight Pacific Time (**PST**) |
| **Core Tokens Per Hour** | **40,000 tokens** | **40,000 tokens** | Rolling / Top-of-hour |
| **Concurrent Requests** | **10 requests** | **10 requests** | Instantaneous |
| **Project-Level Requests** | **50,000 requests / day** | **50,000 requests / day** | Midnight PST |
| **IP-Level Quota** | **10 QPS** (queries / sec) | **10 QPS** | 1-second sliding window |
| **User-Level Quota** | **100 requests / 100s** | **100 requests / 100s** | 100-second window (up to 1,000) |

> [!IMPORTANT]
> **Midnight PST Reset Schedule**: All Google Analytics daily quotas refresh at **12:00:00 AM Pacific Standard Time (`America/Los_Angeles`)**. Genlytics automatically calculates seconds remaining until midnight PST and synchronizes cache TTLs with this exact schedule.

---

## Maximum Resource Utilization Architecture

Without intelligent pacing, an active analytics dashboard can burn through the 40,000 tokens/hour limit in 10 minutes, freezing API access for the remainder of the hour.

Genlytics solves this through a 4-tier optimization engine:

```
                  ┌──────────────────────────────────────────────┐
                  │          Incoming Analytics Request          │
                  └──────────────────────┬───────────────────────┘
                                         ▼
                  ┌──────────────────────────────────────────────┐
                  │ 1. Multi-Tier Check (IP QPS & User 100s)     │
                  └──────────────────────┬───────────────────────┘
                                         ▼
                  ┌──────────────────────────────────────────────┐
                  │ 2. Concurrency Semaphore (Max 10 In-Flight)  │
                  └──────────────────────┬───────────────────────┘
                                         ▼
                  ┌──────────────────────────────────────────────┐
                  │ 3. Adaptive Token Pacing (Hours Remaining)   │
                  └──────────────────────┬───────────────────────┘
                                         ▼
            ┌────────────────────────────┴───────────────────────────┐
            ▼                                                        ▼
    [Quota Optimal]                                        [Quota >85% Consumed]
  Direct GA4 Execution                                    Serve SWR Cached Stale
  Sync Google Ground Truth                                Reserve Tokens for High-Pri
```

### 1. Adaptive Budget Pacing
Genlytics calculates your sustainable burn rate dynamically:
$$\text{Target Hourly Budget} = \frac{\text{Remaining Daily Tokens}}{\text{Hours Remaining Until Midnight PST}}$$
If consumption exceeds 1.5× the sustainable burn rate and approaches the hourly threshold, Genlytics transitions from `optimal` to `pacing_required`, transparently serving stale cached data for read queries while safeguarding interactive requests.

### 2. Atomic Concurrency Semaphore (Max 10)
Prevents bursting beyond Google's hard limit of **10 concurrent requests per property**. If 10 requests are currently in flight, the 11th request:
1. Micro-waits with exponential jitter backoff (20ms – 50ms).
2. If slots remain saturated, returns fresh/stale SWR cache rather than throwing a `429` error.

### 3. Bi-Directional Ground Truth Sync
Every response returned by the GA4 API includes `PropertyQuota` metadata. Genlytics parses `$response->getPropertyQuota()` in real time:
- `$quota->getTokensPerDay()->getRemaining()`
- `$quota->getTokensPerHour()->getRemaining()`
- `$quota->getConcurrentRequests()->getRemaining()`

This ensures Genlytics internal counters never drift from Google's actual server-side counters.

---

## Developer API & Usage

### 1. Check Current Quota & Pacing Status

```php
use zfhassaan\genlytics\facades\AnalyticsFacade as Genlytics;

// Get full typed QuotaStatusDTO
$status = Genlytics::quotaStatus();

echo "Daily Remaining: " . $status->dailyTokensRemaining . " / " . $status->dailyTokensLimit;
echo "Hourly Remaining: " . $status->hourlyTokensRemaining . " / " . $status->hourlyTokensLimit;
echo "Active Concurrent Requests: " . $status->concurrentRequestsActive . " / " . $status->concurrentRequestsLimit;
echo "PST Midnight Reset In: " . $status->secondsUntilDailyReset . " seconds";
echo "Pacing Status: " . $status->pacingStatus; // 'optimal', 'pacing_required', 'critical_throttle'
```

### 2. Get Quick Remaining Tokens

```php
$remainingDaily = Genlytics::remainingTokens(); // e.g. 184520
```

### 3. Exposing Quota in API Responses

Return quota status to frontend dashboards using the standardized `QuotaStatusResource`:

```php
use zfhassaan\genlytics\facades\AnalyticsFacade as Genlytics;
use zfhassaan\genlytics\Http\Resources\QuotaStatusResource;

Route::get('/api/analytics/quota', function () {
    return new QuotaStatusResource(Genlytics::quotaStatus());
});
```

**JSON Output:**
```json
{
  "data": {
    "property_type": "standard",
    "daily_tokens": {
      "limit": 200000,
      "consumed": 15480,
      "remaining": 184520,
      "utilization_percent": 7.74
    },
    "hourly_tokens": {
      "limit": 40000,
      "consumed": 3200,
      "remaining": 36800,
      "utilization_percent": 8.0
    },
    "concurrency": {
      "active": 2,
      "limit": 10
    },
    "project_requests": {
      "limit": 50000,
      "consumed": 642,
      "utilization_percent": 1.28
    },
    "reset_schedule": {
      "seconds_remaining": 38420,
      "midnight_pst": "00:00:00 PST"
    },
    "pacing": {
      "is_throttled": false,
      "status": "optimal"
    }
  }
}
```

---

## Consuming 100% Daily Quota (`consume_all`)

If your application needs to **consume all available daily quota** (e.g. daily pre-warming of all reports, nightly data warehouse exports, or exhaustive funnel breakdowns) so that zero free Google quota is wasted before the midnight PST reset:

### 1. Activating 100% Daily Consumption

#### Globally in `config/analytics.php`:
```php
'quota' => [
    'mode'                    => 'consume_all', // Or 'maximum_utilization'
    'consume_all_daily_quota' => true,          // Consumes up to 100% of 200,000 / 2,000,000 tokens
],
```

#### At Runtime via Facade:
```php
use zfhassaan\genlytics\facades\AnalyticsFacade as Genlytics;

// Enable 100% daily quota consumption
Genlytics::enableAllQuotaConsumption();

// Check if all-quota mode is active
if (Genlytics::isAllQuotaConsumptionEnabled()) {
    // Queries will never be diverted to stale cache until 0 tokens remain
}
```

#### Per-Query with Builder:
```php
$report = Genlytics::query()
    ->last30Days()
    ->dimensions(['country', 'city', 'pagePath'])
    ->metrics(['activeUsers', 'sessions', 'screenPageViews'])
    ->consumeAllQuota() // Zero throttling; uses all remaining tokens
    ->get();
```

### 2. Automated Daily Quota Drainage & Cache Pre-Warming

Genlytics provides a dedicated console command to proactively burn/extract remaining tokens into pre-warmed cache before the midnight PST reset:

```bash
# Proactively pre-warm cache and extract up to 100% daily quota
php artisan genlytics:consume-quota --target-utilization=100

# Dry-run preview of tokens needed without hitting Google API
php artisan genlytics:consume-quota --dry-run

# Or via refresh-cache with the --consume-all flag
php artisan genlytics:refresh-cache --consume-all
```

---

## Real-Time Quota Telemetry & Live Updates

Genlytics provides out-of-the-box real-time telemetry so your dashboards and administrative interfaces can monitor quota depletion live without polling.

### 1. Laravel Echo / WebSockets (`QuotaUsageUpdated`)

Whenever queries execute or ground-truth synchronizes, Genlytics fires `QuotaUsageUpdated` implementing `ShouldBroadcastNow`:

```javascript
// Listen with Laravel Echo (Reverb, Pusher, Soketi)
Echo.channel('genlytics-quota')
    .listen('.quota.updated', (event) => {
        console.log(`Daily Tokens Remaining: ${event.daily_tokens_remaining}`);
        console.log(`Hourly Consumed: ${event.hourly_tokens_consumed} / 40,000`);
        console.log(`Active Concurrency: ${event.concurrent_active} / 10`);
        console.log(`Pacing Status: ${event.pacing_status}`);
        console.log(`PST Reset Countdown: ${event.seconds_until_pst_reset}s`);
    });
```

### 2. Server-Sent Events (SSE) Stream

For lightweight HTTP streaming without WebSocket servers:

```javascript
const evtSource = new EventSource('/genlytics/api/quota/stream');

evtSource.addEventListener('quota.updated', (event) => {
    const data = JSON.parse(event.data);
    updateQuotaGauge(data.daily_tokens.remaining, data.daily_tokens.utilization_percent);
});
```

---

## Maximum Quota Utilization Mode (`maximum_utilization`)

When you want to **extract maximum utility from your Google Analytics 4 API quota** (e.g. for high-volume enterprise ETL, data warehouse syncing, or dense executive dashboards), Genlytics provides a dedicated **Maximum Quota Utilization Mode**:

### Key Features of Maximum Quota Mode:
1. **Unlocks 100% of Token Capacity**: Relaxes early warning thresholds from 85% to 99%, allowing full token consumption without premature stale-cache intervention.
2. **Full Concurrency Throughput**: Maximizes all 10 concurrent request slots with non-blocking spinwait queueing.
3. **GCP User Quota Expansion**: Allows configuring user rate limits up to **1,000 requests per 100 seconds** (the maximum ceiling permitted by Google Analytics in Google Cloud Console).
4. **Pre-Flight Validation**: Calculates estimated token cost before firing queries and validates against Google API constraints.

### Enabling Maximum Quota Utilization

#### 1. Globally in `config/analytics.php`:
```php
'quota' => [
    'mode' => 'maximum_utilization', // 'maximum_utilization', 'balanced', or 'conservative'
    'property_type' => 'standard',   // 200,000 tokens/day (or '360' for 2,000,000 tokens/day)
    'user_requests_per_100s' => 1000,// Maximum permitted by Google Cloud
],
```

#### 2. At Runtime via Facade:
```php
use zfhassaan\genlytics\facades\AnalyticsFacade as Genlytics;

// Enable maximum quota burst at runtime
Genlytics::enableMaximumQuotaUtilization();

// Or switch modes dynamically
Genlytics::setQuotaMode('maximum_utilization');
```

#### 3. Per-Query with Eloquent Builder:
```php
$report = Genlytics::query()
    ->last30Days()
    ->dimensions(['country', 'city', 'pagePath'])
    ->metrics(['activeUsers', 'sessions', 'screenPageViews'])
    ->maxQuota() // Unlocks maximum quota utilization for this execution
    ->get();
```

---

## Quota & Configuration Validations

Genlytics enforces rigorous validation checks on both configuration and runtime query execution to ensure your application stays compliant with Google Cloud policies:

### 1. Configuration Validation Rules

| Setting | Constraint | Error Thrown |
| :--- | :--- | :--- |
| `property_type` | Must be `'standard'` or `'360'` | `QuotaValidationException` (422) |
| `mode` | Must be `'maximum_utilization'`, `'balanced'`, or `'conservative'` | `QuotaValidationException` (422) |
| `concurrent_requests_limit` | Must be between `1` and `10` (Google hard cap) | `QuotaValidationException` (422) |
| `hourly_token_limit` | Cannot exceed `40,000` (Google hard cap) | `QuotaValidationException` (422) |
| `project_daily_request_limit` | Cannot exceed `50,000` requests/day | `QuotaValidationException` (422) |
| `ip_qps_limit` | Cannot exceed `10` queries/second | `QuotaValidationException` (422) |
| `user_requests_per_100s` | Must be between `1` and `1,000` | `QuotaValidationException` (422) |

### 2. Pre-Flight Query Validation

```php
try {
    // Validates query token budget before calling Google API
    Genlytics::validateQuota($estimatedTokens = 5);
} catch (QuotaExceededException $e) {
    Log::warning("Quota check failed: " . $e->getMessage());
    echo "Resets in " . $e->getResetInSeconds() . " seconds (Midnight PST)";
}
```

---

## Configuration (`config/analytics.php`)

```php
'quota' => [
    // Operational Mode: 'consume_all', 'maximum_utilization', 'balanced', 'conservative'
    'mode'                       => env('GENLYTICS_QUOTA_MODE', 'balanced'),

    // 'standard' (200,000) or '360' (2,000,000)
    'property_type'              => env('GENLYTICS_PROPERTY_TYPE', 'standard'),

    // Core limits
    'daily_token_limit'          => env('GENLYTICS_DAILY_TOKEN_LIMIT', 200000),
    'hourly_token_limit'         => env('GENLYTICS_HOURLY_TOKEN_LIMIT', 40000),
    'concurrent_requests_limit'  => env('GENLYTICS_CONCURRENT_LIMIT', 10),
    'project_daily_request_limit'=> env('GENLYTICS_PROJECT_DAILY_LIMIT', 50000),
    'ip_qps_limit'               => env('GENLYTICS_IP_QPS_LIMIT', 10),
    'user_requests_per_100s'     => env('GENLYTICS_USER_100S_LIMIT', 1000),

    // PST Reset
    'timezone'                   => 'America/Los_Angeles',

    // Threshold where SWR cache takes over (1.0 for consume_all, 0.99 for maximum_utilization, 0.85 for balanced)
    'warning_threshold'          => 0.85,

    // Strategy when exhausted: 'cache_fallback' or 'strict'
    'exhaustion_strategy'        => env('GENLYTICS_QUOTA_STRATEGY', 'cache_fallback'),

    // Option to Consume All Daily Quota
    'consume_all_daily_quota'    => env('GENLYTICS_CONSUME_ALL_QUOTA', false),

    // Real-Time Telemetry Broadcasting
    'realtime_broadcasting'      => env('GENLYTICS_REALTIME_QUOTA_BROADCAST', true),
    'broadcast_channel'          => env('GENLYTICS_QUOTA_CHANNEL', 'genlytics-quota'),
],
```

---

## Automated Test Coverage & Verification

Genlytics includes comprehensive test suites covering all quota algorithms, concurrency locks, PST timezone mathematics, operational modes, and real-time broadcasting:

### 1. Test Suite Summary

| Test Suite | Tests | Assertions | Status | Coverage Scope |
| :--- | :--- | :--- | :--- | :--- |
| **`GenlyticsTest`** | 20 | 54 | `100% PASS` | Facade methods, e-commerce funnels, quota drainage, resource helpers |
| **`QuotaManagerTest`** | 17 | 79 | `100% PASS` | Quota tracking, modes (`consume_all`, `maximum_utilization`), PST math, concurrency locks, validation exceptions |
| **`CacheManagerTest`** | 15 | 26 | `100% PASS` | Configurable TTL per report type, cache tags, custom durations |
| **`ResourcesTest`** | 12 | 49 | `100% PASS` | Package versioning (`1.0.5`), typed API resource envelopes, JSON responses |
| **`AnalyticsServiceTest`** | 12 | 26 | `100% PASS` | Service orchestration, stale cache fallback, custom TTLs |
| **`AnalyticsQueryBuilderTest`** | 11 | 27 | `100% PASS` | Fluent chaining, `->consumeAllQuota()`, `->maxQuota()`, resource casting |
| **`AnalyticsRepositoryTest`** | 11 | 13 | `100% PASS` | GA4 Data API client integration, concurrency slots, batch runs |
| **`AnalyticsDTO`** | 9 | 26 | `100% PASS` | Strongly-typed DTO conversions for pages, countries, funnels, products |
| **`FetchAnalyticsDataJobTest`** | 8 | 15 | `100% PASS` | Non-blocking async queue jobs, unique locks, and retry strategies |
| **`DataTransformerTest`** | 8 | 46 | `100% PASS` | GA4 protobuf response transformation to clean arrays/JSON |
| **`AnalyticsServiceProviderTest`** | 7 | 19 | `100% PASS` | Service provider boot, config publishing, singleton instances, aliases |
| **`GenlyticsIntegrationTest`** | 7 | 10 | `100% PASS` | Laravel container bindings, facade registration, configuration |
| **`ExceptionsTest`** | 6 | 13 | `100% PASS` | QuotaExceededException, QuotaValidationException, error envelopes |
| **`AnalyticsEventsTest`** | 5 | 18 | `100% PASS` | `QuotaUsageUpdated` broadcast payload, channel routing, cache events |
| **`RefreshAnalyticsCacheTest`** | 4 | 9 | `100% PASS` | Cache clear, refresh specific types, `--consume-all` flag |
| **`ConsumeDailyQuotaTest`** | 3 | 12 | `100% PASS` | Artisan `genlytics:consume-quota` command, dry-run, target utilization |
| **`ServiceProviderTest`** | 3 | 6 | `100% PASS` | Service provider boot, config publishing, singleton instances |
| **`QuotaStreamControllerTest`** | 2 | 10 | `100% PASS` | SSE stream headers, JSON telemetry endpoint |
| **`UpdateRealTimeCacheTest`** | 2 | 4 | `100% PASS` | Automatic real-time background cache re-scheduling |
| **`QuotaStatusDTOTest`** | 1 | 5 | `100% PASS` | DTO serialization, utilization percentages, pacing states |
| **Total Suite** | **160 Tests** | **484 Assertions** | `100% PASS` | **Full Package Code Coverage (90.43% lines)** |

### 2. Running Test Coverage Commands

```bash
# Run complete test suite (160 tests, 484 assertions):
composer test:genlytics
# or:
php packages/zfhassaan/genlytics/test-runner.php

# Run with full line code coverage text summary:
composer test:genlytics:coverage
# or:
php -d xdebug.mode=coverage packages/zfhassaan/genlytics/test-runner.php --coverage-text

# Generate HTML code coverage report:
composer test:genlytics:coverage-html
# or:
php -d xdebug.mode=coverage packages/zfhassaan/genlytics/test-runner.php --coverage-html coverage
```

### 3. Testing Real-Time Quota Broadcasting in Laravel

```php
use Illuminate\Support\Facades\Event;
use zfhassaan\genlytics\Events\QuotaUsageUpdated;
use zfhassaan\genlytics\facades\AnalyticsFacade as Genlytics;

public function test_it_dispatches_realtime_broadcast_on_query()
{
    Event::fake([QuotaUsageUpdated::class]);

    Genlytics::query()->today()->metric('activeUsers')->get();

    Event::assertDispatched(QuotaUsageUpdated::class, function ($event) {
        return $event->tokensConsumed >= 1
            && $event->channel === 'genlytics-quota'
            && $event->status->dailyTokensRemaining > 0;
    });
}
```

### 3. Testing Artisan Quota Commands

```php
// Test dry-run token estimation
$this->artisan('genlytics:consume-quota', ['--dry-run' => true])
    ->expectsOutput('DRY RUN: Pre-flight estimation complete. Exiting without calling Google API.')
    ->assertExitCode(0);

// Test --consume-all cache pre-warming
$this->artisan('genlytics:refresh-cache', ['--consume-all' => true])
    ->expectsOutput('🚀 Activating Full Daily Quota Consumption Mode...')
    ->assertExitCode(0);
```

