# Response DTOs & Standardized API Resources

Genlytics provides a rich suite of **Data Transfer Objects (DTOs)** and **Laravel API Resources** (`JsonResource`), bringing strict type-safety, IDE autocompletion, embedded package versioning, and standardized JSON API envelopes to all your Google Analytics data.

---

## 1. Why Use Response DTOs?

Raw analytics data is traditionally returned as loose associative arrays:
```php
// Unsafe & prone to typos:
$views = $row['metrics']['screenPageViews']; // If misspelled as 'screenViews', silent bug!
```

With Genlytics DTOs, responses are strongly typed with IDE autocomplete:
```php
// Type-safe & autocompleted:
$dto = PageReportDTO::fromRow($row);
echo $dto->views;       // (int) 5420
echo $dto->path;        // (string) "/pricing"
echo $dto->activeUsers; // (int) 1200
```

---

## 2. Available DTO Classes

Located in `zfhassaan\genlytics\DTOs`:

| DTO Class | Key Properties | Ideal Use Case |
| :--- | :--- | :--- |
| `AnalyticsRowDTO` | `$dimensions`, `$metrics`, `getIntMetric()`, `getFloatMetric()` | Generic queries with dynamic dimensions & metrics |
| `PageReportDTO` | `$path`, `$title`, `$views`, `$activeUsers`, `$averageDuration` | Top URLs, blog posts, landing page metrics |
| `CountryReportDTO` | `$country`, `$activeUsers`, `$sessions` | Geographic distribution & international traffic |
| `TrafficSourceDTO` | `$source`, `$medium`, `$sessions`, `$activeUsers`, `$bounceRate` | Marketing attribution & acquisition channels |
| `ProductPerformanceDTO`| `$id`, `$name`, `$category`, `$revenue`, `$itemsPurchased`, `$cartToViewRate` | E-commerce items & sales performance |
| `CategoryPerformanceDTO`| `$category`, `$revenue`, `$units`, `$itemsViewed`, `$cartToViewRate` | Store product category volume & sales |
| `FunnelPipelineDTO` | `$name`, `$totalEntries`, `$totalCompletions`, `$overallConversionRate`, `$steps` | Sequential conversion & drop-off analytics |
| `QuotaStatusDTO` | `$dailyTokensRemaining`, `$hourlyTokensRemaining`, `$pacingStatus`, `$mode` | Live GA4 quota monitoring telemetry |
| `UserEngagementDTO` | `$activeUsers`, `$newUsers`, `$engagedSessions`, `$engagementRate` | High-level executive KPI dashboards |
| `DeviceCategoryDTO` | `$category`, `$activeUsers`, `$sessions` | Mobile vs Desktop responsive design analytics |

---

## 3. Query Builder DTO & Resource Integration

The `AnalyticsQueryBuilder` features instant DTO and API Resource casting methods:

```php
use zfhassaan\genlytics\facades\AnalyticsFacade as Genlytics;

// Cast to generic AnalyticsRowDTOs:
$rows = Genlytics::query()->last7Days()->dimensions('country')->metrics('activeUsers')->toDto();

// Cast directly to typed DTOs:
$pages = Genlytics::query()->last30Days()->dimensions('pagePath', 'pageTitle')->metrics('screenPageViews', 'activeUsers')->toPageDto();
$countries = Genlytics::query()->last30Days()->dimension('country')->metrics('activeUsers', 'sessions')->toCountryDto();
$products = Genlytics::query()->last30Days()->dimensions('itemId', 'itemName')->toProductDto();

// Cast directly to Laravel API Resources (with package version envelope):
$pageResources = Genlytics::query()->last30Days()->toPageResource();
$countryResources = Genlytics::query()->last30Days()->toCountryResource();
$productResources = Genlytics::query()->last30Days()->toProductResource();

// Return standardized HTTP JsonResponse directly from controller:
return Genlytics::query()->last7Days()->toResponse();
```

---

## 4. Standardized API Resources with Embedded Package Version

All Genlytics API Resources implement `HasGenlyticsVersion`. When returned from Laravel controllers or serialized to JSON, they automatically include the active **package version** (`1.0.5`), success status, count, and ISO 8601 timestamps.

### Output JSON Format:
```json
{
  "success": true,
  "version": "1.0.5",
  "data": [
    {
      "dimensions": {
        "country": "United States",
        "city": "New York"
      },
      "metrics": {
        "activeUsers": "1420",
        "sessions": "1890"
      }
    }
  ],
  "meta": {
    "version": "1.0.5",
    "count": 1,
    "timestamp": "2026-10-25T10:40:07+00:00"
  }
}
```

---

## 5. Instant Controller One-Liners via Facade

Every major query function in `Genlytics` provides a corresponding `*Resource()` method that returns an API Resource ready for controller output:

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use zfhassaan\genlytics\facades\AnalyticsFacade as Genlytics;

class AnalyticsApiController extends Controller
{
    // Return Top Pages with version 1.0.5 envelope:
    public function pages()
    {
        return Genlytics::topPagesResource(limit: 10);
    }

    // Return Top Countries:
    public function countries()
    {
        return Genlytics::topCountriesResource(limit: 10);
    }

    // Return Top Products:
    public function products()
    {
        return Genlytics::topProductsResource(limit: 10);
    }

    // Return E-Commerce Funnel Pipeline:
    public function funnel()
    {
        return Genlytics::ecommerceFunnelResource();
    }

    // Return Live Quota Telemetry:
    public function quota()
    {
        return Genlytics::quotaStatusResource();
    }
}
```
