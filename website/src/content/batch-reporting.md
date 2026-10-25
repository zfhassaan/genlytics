# High-Performance Batch Reporting

When developing modern analytics dashboards, a single view commonly displays multiple data cards:
- Top Active Countries
- Traffic Acquisition Channels
- Most Viewed URLs
- Daily Trend of Active Users

---

## The Sequential Request Problem

Under a standard implementation, loading these 4 widgets triggers **4 separate network requests** to Google's servers:

```
Widget 1: HTTP Request 1 (300ms) ────────► 
Widget 2: HTTP Request 2 (300ms) ────────────────────► 
Widget 3: HTTP Request 3 (300ms) ────────────────────────────────► 
Widget 4: HTTP Request 4 (300ms) ────────────────────────────────────────────►
Total Latency: ~1,200ms | Quota Tokens Expended: 4 separate requests
```

---

## The Batching Solution (`batchRunReports`)

Google Analytics 4 Data API provides a dedicated batching interface. Genlytics exposes this natively via `Genlytics::getBatchReports()` and `Genlytics::batchRunReports()`:

```
[ Request 1 + Request 2 + Request 3 + Request 4 ] (1 HTTP Call: 350ms) ──────►
Total Latency: ~350ms (70%+ faster) | Quota Tokens Conserved
```

---

## Code Example: Loading a Complete Dashboard

```php
use zfhassaan\genlytics\facades\AnalyticsFacade as Genlytics;

class AnalyticsDashboardController extends Controller
{
    public function index()
    {
        $dashboardData = Genlytics::getBatchReports([
            'traffic_sources' => [
                'dateRange'  => ['start_date' => '30daysAgo', 'end_date' => 'today'],
                'dimensions' => ['sessionSource'],
                'metrics'    => ['sessions', 'activeUsers'],
                'options'    => ['limit' => 10],
            ],
            'top_pages' => [
                'dateRange'  => ['start_date' => '30daysAgo', 'end_date' => 'today'],
                'dimensions' => ['pagePath'],
                'metrics'    => ['screenPageViews'],
                'options'    => ['limit' => 10],
            ],
            'geo_distribution' => [
                'dateRange'  => ['start_date' => '30daysAgo', 'end_date' => 'today'],
                'dimensions' => ['country'],
                'metrics'    => ['activeUsers'],
                'options'    => ['limit' => 10],
            ],
        ]);

        return view('dashboard.analytics', [
            'sources'   => $dashboardData['traffic_sources'] ?? [],
            'pages'     => $dashboardData['top_pages'] ?? [],
            'countries' => $dashboardData['geo_distribution'] ?? [],
        ]);
    }
}
```

