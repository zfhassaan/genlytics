# Getting Started with Genlytics

**Genlytics** is an enterprise-grade Google Analytics 4 (GA4) package for Laravel, engineered with the **Repository pattern**, **Event-driven architecture**, and **high-performance caching**.

---

## Requirements

- **PHP**: 8.0 or higher (PHP 8.0, 8.1, 8.2, 8.3, 8.4, 8.5, and 8.6 fully supported)
- **Laravel**: 10.x, 11.x, 12.x, or 13.x
- A **Google Analytics 4** property (Universal Analytics is retired)
- A **Google Cloud Platform (GCP)** project with Analytics Data API enabled
- A GCP Service Account JSON credential key

---

## Installation

Install the package via Composer:

```bash
composer require zfhassaan/genlytics
```

### Auto-Discovery
Genlytics automatically registers its ServiceProvider (`zfhassaan\genlytics\provider\AnalyticsServiceProvider`) and Facade alias (`Genlytics`). No manual registration in `config/app.php` is needed.

---

## Google Cloud & GA4 Setup

1. **Enable the API**:
   - Go to [Google Cloud Console](https://console.cloud.google.com/).
   - Select your project and navigate to **APIs & Services** > **Library**.
   - Search for **Google Analytics Data API** and click **Enable**.

2. **Create Service Account**:
   - Go to **IAM & Admin** > **Service Accounts**.
   - Click **Create Service Account**, give it a name (e.g. `ga4-analytics-reader`), and click Create.
   - Click the newly created service account, navigate to the **Keys** tab, click **Add Key** > **Create new key**, and choose **JSON**.
   - Download the JSON file and save it securely in your Laravel app (e.g. `storage/app/analytics/service-account.json`).

3. **Grant Access in Google Analytics**:
   - Open [Google Analytics](https://analytics.google.com/).
   - Go to **Admin** (gear icon) > **Property Access Management**.
   - Click **+** > **Add users**.
   - Enter your Service Account email address (e.g., `ga4-analytics-reader@your-project.iam.gserviceaccount.com`).
   - Assign the **Viewer** or **Analyst** role and click **Add**.

4. **Find your Property ID**:
   - In Google Analytics Admin, go to **Property Settings** > **Property Details**.
   - Copy the numeric **Property ID** (e.g., `123456789`).

---

## Configuration

Publish the package configuration:

```bash
php artisan vendor:publish --tag=genlytics-config
```

Add the following environment variables to your `.env` file:

```env
# Required GA4 Settings
GENLYTICS_PROPERTY_ID=123456789
GENLYTICS_CREDENTIALS=storage/app/analytics/service-account.json

# Performance & Caching (Recommended)
GENLYTICS_ENABLE_CACHE=true
GENLYTICS_CACHE_STORE=redis
GENLYTICS_CACHE_PREFIX=genlytics
GENLYTICS_CACHE_USE_TAGS=true

# Background Queue Processing
GENLYTICS_USE_BACKGROUND_JOBS=true
GENLYTICS_QUEUE_CONNECTION=redis
GENLYTICS_QUEUE_NAME=analytics
```

---

## Your First Query

```php
use zfhassaan\genlytics\facades\AnalyticsFacade as Genlytics;

// Fetch top 5 countries by active users for the last 7 days:
$data = Genlytics::getReport(
    period: ['start_date' => '7daysAgo', 'end_date' => 'today'],
    dimension: 'country',
    metrics: 'activeUsers',
    options: ['limit' => 5]
);

// Returns clean PHP array:
// [
//     ['dimensions' => ['country' => 'United States'], 'metrics' => ['activeUsers' => 1420]],
//     ['dimensions' => ['country' => 'United Kingdom'], 'metrics' => ['activeUsers' => 840]],
//     ...
// ]
```

---

## Running Test Suites & Code Coverage

Genlytics is rigorously verified with **160 automated tests** and **484 assertions** across 19 test suites, ensuring **90.43% line code coverage** (100% on commands, events, API resources, exceptions, DTOs, background jobs, and listeners) with zero test failures.

For the comprehensive class-by-class verification report, see the [Test Coverage & Verification](test-coverage) section.

### Test Execution Commands

From the root project directory:

```bash
# 1. Run all 160 test cases across all test suites:
composer test:genlytics
# or directly:
php packages/zfhassaan/genlytics/test-runner.php

# 2. Run with detailed test-dox format:
php packages/zfhassaan/genlytics/test-runner.php --testdox

# 3. Run a specific test suite or method:
php packages/zfhassaan/genlytics/test-runner.php --filter QuotaManagerTest
php packages/zfhassaan/genlytics/test-runner.php --filter test_it_dispatches_realtime_broadcast

# 4. Run test coverage with text report in console (90.4% coverage):
composer test:genlytics:coverage
# or directly:
php -d xdebug.mode=coverage packages/zfhassaan/genlytics/test-runner.php --coverage-text

# 5. Generate interactive HTML coverage report in coverage/ directory:
composer test:genlytics:coverage-html
# or directly:
php -d xdebug.mode=coverage packages/zfhassaan/genlytics/test-runner.php --coverage-html coverage
```

If working directly inside `packages/zfhassaan/genlytics`:

```bash
# Run tests:
composer test

# Run code coverage:
composer run test:coverage

# Generate HTML coverage report:
composer run test:coverage-html
```

### Complete Test Suites Breakdown

| Test Suite | Tests | Assertions | Focus Area | Status |
| :--- | :--- | :--- | :--- | :--- |
| **`GenlyticsTest`** | 20 | 54 | Facade methods, e-commerce funnels, quota drain, resource helpers | `100% PASS` |
| **`QuotaManagerTest`** | 17 | 79 | Quota tracking, concurrency locks, PST math, operational modes | `100% PASS` |
| **`CacheManagerTest`** | 15 | 26 | Configurable TTL per report type, cache tags, custom durations | `100% PASS` |
| **`ResourcesTest`** | 12 | 49 | Package versioning (`1.0.5`), API resource wrappers, envelope formats | `100% PASS` |
| **`AnalyticsServiceTest`** | 12 | 26 | Service orchestration, stale cache fallback, custom TTLs | `100% PASS` |
| **`AnalyticsQueryBuilderTest`** | 11 | 27 | Fluent query builder, date scopes, casting to DTOs & API resources | `100% PASS` |
| **`AnalyticsRepositoryTest`** | 11 | 13 | GA4 Data API client integration, concurrency slots, batch runs | `100% PASS` |
| **`AnalyticsDTO`** | 9 | 26 | Typed DTO mappings for pages, countries, funnels, products | `100% PASS` |
| **`FetchAnalyticsDataJobTest`** | 8 | 15 | Non-blocking async queue jobs, unique locks, and retry strategies | `100% PASS` |
| **`DataTransformerTest`** | 8 | 46 | GA4 protobuf response transformation to clean arrays/JSON | `100% PASS` |
| **`AnalyticsServiceProviderTest`** | 7 | 19 | Service provider bindings, singletons, published assets, aliases | `100% PASS` |
| **`GenlyticsIntegrationTest`** | 7 | 10 | Laravel container bindings, facade registration, configuration | `100% PASS` |
| **`ExceptionsTest`** | 6 | 13 | QuotaExceededException, QuotaValidationException, error envelopes | `100% PASS` |
| **`AnalyticsEventsTest`** | 5 | 18 | Event dispatching (`QuotaUsageUpdated`, `AnalyticsCacheUpdated`) | `100% PASS` |
| **`RefreshAnalyticsCacheTest`** | 4 | 9 | Artisan `genlytics:refresh-cache` and `--consume-all` flag | `100% PASS` |
| **`ConsumeDailyQuotaTest`** | 3 | 12 | Artisan `genlytics:consume-quota` command, dry-run mode | `100% PASS` |
| **`ServiceProviderTest`** | 3 | 6 | Service provider boot, config publishing, singleton instances | `100% PASS` |
| **`QuotaStreamControllerTest`** | 2 | 10 | Real-time SSE streams and telemetry JSON resource endpoint | `100% PASS` |
| **`UpdateRealTimeCacheTest`** | 2 | 4 | Automatic real-time background cache re-scheduling | `100% PASS` |
| **`QuotaStatusDTOTest`** | 1 | 5 | Quota status serialization and remaining calculation | `100% PASS` |
| **Total** | **160 Tests** | **484 Assertions** | **Full Package Coverage (90.43% lines)** | **100% PASS** |


