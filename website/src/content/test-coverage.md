# Test Suite & Code Coverage Report

Genlytics is rigorously verified with an enterprise-grade automated test suite executed via PHPUnit 10 and PHP 8.3 with Xdebug code coverage telemetry. Every core algorithm, concurrency lock, API resource, fluent query builder scope, and caching strategy is validated under isolated unit and integration test harnesses.

---

## 1. Executive Summary

| Metric | Measured Value | Standard | Health Status |
| :--- | :--- | :--- | :--- |
| **Total Test Suites** | **19 Suites** | 100% Autonomous | `VERIFIED` |
| **Total Test Cases** | **160 Tests** | 0 Failures, 0 Errors | `100% PASS` |
| **Total Assertions** | **484 Assertions** | Comprehensive assertions | `OPTIMAL` |
| **Line Code Coverage** | **90.43% (1,692 / 1,871 lines)** | Industry leading | `EXCELLENT` |
| **Method Code Coverage** | **80.51% (223 / 277 methods)** | High API surface coverage | `OPTIMAL` |
| **Classes at 100%** | **31 Classes (70.45%)** | Critical paths verified | `100% COVERAGE` |

---

## 2. Component-by-Component Coverage Breakdown

Below is the verified coverage report generated directly from Xdebug code coverage processing:

### Core Commands, Events & Jobs (100% Coverage)
| Component / Class | Methods | Lines Covered | Coverage % |
| :--- | :--- | :--- | :--- |
| `zfhassaan\genlytics\Commands\ConsumeDailyQuota` | 1 / 1 (100%) | 63 / 63 | **100.00%** |
| `zfhassaan\genlytics\Commands\RefreshAnalyticsCache` | 1 / 1 (100%) | 21 / 21 | **100.00%** |
| `zfhassaan\genlytics\Events\AnalyticsCacheUpdated` | 1 / 1 (100%) | 1 / 1 | **100.00%** |
| `zfhassaan\genlytics\Events\AnalyticsDataFetched` | 1 / 1 (100%) | 1 / 1 | **100.00%** |
| `zfhassaan\genlytics\Events\AnalyticsDataRequested` | 1 / 1 (100%) | 1 / 1 | **100.00%** |
| `zfhassaan\genlytics\Events\AnalyticsQueryFailed` | 1 / 1 (100%) | 1 / 1 | **100.00%** |
| `zfhassaan\genlytics\Events\QuotaUsageUpdated` | 4 / 4 (100%) | 25 / 25 | **100.00%** |
| `zfhassaan\genlytics\Listeners\UpdateRealTimeCache` | 2 / 2 (100%) | 5 / 5 | **100.00%** |
| `zfhassaan\genlytics\Jobs\FetchAnalyticsDataJob` | 7 / 7 (100%) | 56 / 56 | **100.00%** |
| `zfhassaan\genlytics\Exceptions\QuotaExceededException` | 5 / 5 (100%) | 17 / 17 | **100.00%** |
| `zfhassaan\genlytics\Exceptions\QuotaValidationException` | 4 / 4 (100%) | 14 / 14 | **100.00%** |
| `zfhassaan\genlytics\facades\AnalyticsFacade` | 1 / 1 (100%) | 1 / 1 | **100.00%** |

---

### Strongly-Typed Data Transfer Objects (DTOs)
| DTO Class | Methods | Lines Covered | Coverage % |
| :--- | :--- | :--- | :--- |
| `zfhassaan\genlytics\DTOs\AnalyticsRowDTO` | 8 / 8 (100%) | 16 / 16 | **100.00%** |
| `zfhassaan\genlytics\DTOs\CategoryPerformanceDTO` | 4 / 4 (100%) | 23 / 23 | **100.00%** |
| `zfhassaan\genlytics\DTOs\CountryReportDTO` | 4 / 4 (100%) | 14 / 14 | **100.00%** |
| `zfhassaan\genlytics\DTOs\FunnelPipelineDTO` | 3 / 3 (100%) | 9 / 9 | **100.00%** |
| `zfhassaan\genlytics\DTOs\FunnelStepDTO` | 3 / 3 (100%) | 12 / 12 | **100.00%** |
| `zfhassaan\genlytics\DTOs\PageReportDTO` | 4 / 4 (100%) | 18 / 18 | **100.00%** |
| `zfhassaan\genlytics\DTOs\ProductPerformanceDTO` | 4 / 4 (100%) | 31 / 31 | **100.00%** |
| `zfhassaan\genlytics\DTOs\TrafficSourceDTO` | 4 / 4 (100%) | 18 / 18 | **100.00%** |
| `zfhassaan\genlytics\DTOs\UserEngagementDTO` | 4 / 4 (100%) | 17 / 17 | **100.00%** |
| `zfhassaan\genlytics\DTOs\QuotaStatusDTO` | 1 / 2 (50%) | 59 / 62 | **95.16%** |

---

### Standardized API Resources (`JsonResource` - 100% Coverage)
| API Resource Class | Methods | Lines Covered | Coverage % |
| :--- | :--- | :--- | :--- |
| `Http\Resources\AnalyticsResource` | 1 / 1 (100%) | 15 / 15 | **100.00%** |
| `Http\Resources\Concerns\HasGenlyticsVersion` | 1 / 1 (100%) | 8 / 8 | **100.00%** |
| `Http\Resources\AnalyticsResourceCollection` | 2 / 2 (100%) | 12 / 12 | **100.00%** |
| `Http\Resources\CategoryPerformanceResource` | 1 / 1 (100%) | 4 / 4 | **100.00%** |
| `Http\Resources\CountryReportResource` | 1 / 1 (100%) | 4 / 4 | **100.00%** |
| `Http\Resources\FunnelPipelineResource` | 1 / 1 (100%) | 3 / 3 | **100.00%** |
| `Http\Resources\PageReportResource` | 1 / 1 (100%) | 4 / 4 | **100.00%** |
| `Http\Resources\ProductPerformanceResource` | 1 / 1 (100%) | 4 / 4 | **100.00%** |
| `Http\Resources\QuotaStatusResource` | 1 / 1 (100%) | 38 / 38 | **100.00%** |
| `Http\Resources\TrafficSourceResource` | 1 / 1 (100%) | 4 / 4 | **100.00%** |

---

### Core Querying, Services & Repositories
| Class | Methods | Lines Covered | Coverage % |
| :--- | :--- | :--- | :--- |
| `zfhassaan\genlytics\Genlytics` | 46 / 55 (83.6%) | 296 / 309 | **95.79%** |
| `zfhassaan\genlytics\Query\AnalyticsQueryBuilder` | 40 / 45 (88.9%) | 130 / 138 | **94.20%** |
| `zfhassaan\genlytics\Services\CacheManager` | 14 / 17 (82.4%) | 62 / 67 | **92.54%** |
| `zfhassaan\genlytics\Repositories\AnalyticsRepository`| 5 / 11 (45.5%) | 115 / 125 | **92.00%** |
| `zfhassaan\genlytics\Services\DataTransformer` | 2 / 4 (50.0%) | 57 / 62 | **91.94%** |
| `zfhassaan\genlytics\Services\AnalyticsService` | 12 / 18 (66.7%) | 142 / 163 | **87.12%** |
| `zfhassaan\genlytics\Services\QuotaManager` | 21 / 31 (67.7%) | 289 / 339 | **85.25%** |
| `provider\AnalyticsServiceProvider` | 2 / 3 (66.7%) | 71 / 87 | **81.61%** |
| `zfhassaan\genlytics\Http\Controllers\QuotaStreamController` | 2 / 3 (66.7%) | 11 / 24 | **45.83%** |

---

## 3. How to Run the Test Suite & Coverage Reports

### Run All 160 Unit & Integration Tests
```bash
# Using Composer shortcut:
composer test:genlytics

# Or directly with PHP:
php packages/zfhassaan/genlytics/test-runner.php
```

### Run with TestDox Verbose Output
```bash
php packages/zfhassaan/genlytics/test-runner.php --testdox
```

### Run Terminal Text Code Coverage (90.4%)
```bash
composer test:genlytics:coverage
# or:
php -d xdebug.mode=coverage packages/zfhassaan/genlytics/test-runner.php --coverage-text
```

### Generate Interactive HTML Coverage Report
```bash
composer test:genlytics:coverage-html
# or:
php -d xdebug.mode=coverage packages/zfhassaan/genlytics/test-runner.php --coverage-html coverage
```
The interactive HTML coverage report is generated directly into the `coverage/` directory:
- Open `coverage/index.html` in your browser to inspect line-by-line green/red code coverage for any class.
