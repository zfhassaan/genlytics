# Fluent Query Builder & Dedicated Helpers

Genlytics provides an **Eloquent-like fluent query builder** and a suite of **dedicated helper functions**, making GA4 data fetching intuitive, readable, and developer-friendly without having to write raw Protobuf options.

---

## 1. Eloquent-Style Fluent Query Builder

Start any custom query using `Genlytics::query()`:

```php
use zfhassaan\genlytics\facades\AnalyticsFacade as Genlytics;

// Build a custom query with method chaining:
$countries = Genlytics::query()
    ->last30Days()
    ->dimensions('country', 'city')
    ->metrics('activeUsers', 'sessions')
    ->orderBy('activeUsers', 'DESC')
    ->limit(10)
    ->get(); // Returns an Illuminate\Support\Collection!

// Process with standard Laravel Collection methods:
$topCountry = $countries->first();
$usUsers = $countries->where('dimensions.country', 'United States');
```

### Predefined Date Ranges
No need to format date strings manually:

```php
Genlytics::query()->today()->get();
Genlytics::query()->yesterday()->get();
Genlytics::query()->last7Days()->get();
Genlytics::query()->last14Days()->get();
Genlytics::query()->last30Days()->get();
Genlytics::query()->last90Days()->get();
Genlytics::query()->period('2026-01-01', '2026-06-30')->get();
```

### Chaining Dimensions & Metrics

```php
// Pass multiple as separate arguments or as an array:
Genlytics::query()
    ->dimensions('pagePath', 'pageTitle', 'deviceCategory')
    ->metrics('screenPageViews', 'activeUsers', 'bounceRate')
    ->limit(25)
    ->offset(0)
    ->get();
```

### Execution & Caching Helpers

| Method | Return Type | Description |
| :--- | :--- | :--- |
| `->get()` | `Illuminate\Support\Collection` | Returns rows wrapped in an Eloquent-like Collection |
| `->toArray()` | `array` | Returns pure PHP array (maximum speed & minimal memory) |
| `->first()` | `?array` | Returns only the first row or null |
| `->count()` | `int` | Returns count of rows |
| `->toJson()` | `Illuminate\Http\JsonResponse` | Formats as JSON response for API controllers |
| `->remember(int $ttl)` | `self` | Caches query result for specified integer seconds |
| `->cacheFor($duration)` | `self` | Accepts integer seconds, `\DateInterval`, or `\DateTimeInterface` |
| `->ttl($duration)` | `self` | Alias for `remember()` / `cacheFor()` |
| `->dontCache()` | `self` | Bypasses cache entirely (fetches ground truth from GA4) |
| `->noCache()` | `self` | Alias for `dontCache()` |
| `->fresh()` | `self` | Bypasses cache and fetches fresh data directly from GA4 |

### Query-Level Cache Lifetimes
You can fine-tune caching on every single query without changing global configuration:

```php
// Integer seconds (2 hours)
$data = Genlytics::query()->last7Days()->dimensions('country')->metrics('activeUsers')->remember(7200)->get();

// DateInterval (e.g., 45 minutes)
$data = Genlytics::query()->today()->metric('screenPageViews')->cacheFor(new \DateInterval('PT45M'))->get();

// DateTime / Carbon (until end of today)
$data = Genlytics::query()->dimension('deviceCategory')->ttl(now()->endOfDay())->get();

// Force fresh query ground-truth
$fresh = Genlytics::query()->last7Days()->metrics('activeUsers')->dontCache()->get();
```

---

## 2. Dedicated One-Liner Query Methods

For the 90% of use cases where you need standard reports, Genlytics provides pre-optimized, dedicated methods:

### Content & Pages
```php
// Top 10 viewed pages for the last 30 days:
$pages = Genlytics::topPages(limit: 10, period: '30daysAgo');

// Each row contains 'pagePath', 'pageTitle', 'screenPageViews', 'activeUsers', 'averageSessionDuration'
```

### Geography & Traffic
```php
// Top countries by active users:
$countries = Genlytics::topCountries(limit: 10, period: '30daysAgo');

// Top cities:
$cities = Genlytics::topCities(limit: 10, period: '30daysAgo');

// Acquisition channels & mediums:
$sources = Genlytics::trafficSources(limit: 10, period: '30daysAgo');
```

### User Behavior & Technology
```php
// Overall engagement stats (newUsers, engagedSessions, engagementRate):
$engagement = Genlytics::userEngagement(period: '30daysAgo');

// Device categories (mobile, desktop, tablet):
$devices = Genlytics::deviceCategories(period: '30daysAgo');

// Top web browsers (Chrome, Safari, Firefox):
$browsers = Genlytics::topBrowsers(limit: 5);

// Conversions by event name:
$events = Genlytics::conversions(limit: 10);
```

### Time Trends & Counts
```php
// Daily trend of active users over the last 30 days:
$trend = Genlytics::dailyTrend(days: 30);

// Total active users count as integer:
$totalUsers = Genlytics::activeUsers(period: '30daysAgo'); // e.g. 14,250

// Total sessions count as integer:
$totalSessions = Genlytics::totalSessions(period: '30daysAgo'); // e.g. 21,800
```

---

## 3. Dedicated Real-Time Helpers

Query active users on your site right now (last 30 minutes):

```php
// Count of active users right now:
$liveUsers = Genlytics::realtimeActiveUsers(); // int

// Top pages being viewed right now:
$activePages = Genlytics::realtimeTopPages(limit: 5);

// Top countries active right now:
$activeCountries = Genlytics::realtimeTopCountries(limit: 5);
```

