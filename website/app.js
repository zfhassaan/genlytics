// Genlytics Documentation Vue 3 Application
const { createApp, ref, computed, watch, onMounted, onUnmounted, nextTick } = Vue;

const docsContent = {
  "getting-started": "# Getting Started with Genlytics\n\n**Genlytics** is an enterprise-grade Google Analytics 4 (GA4) package for Laravel, engineered with the **Repository pattern**, **Event-driven architecture**, and **high-performance caching**.\n\n---\n\n## Requirements\n\n- **PHP**: 8.1 or higher (PHP 8.2 & 8.3 fully supported)\n- **Laravel**: 10.x, 11.x, or 12.x\n- A **Google Analytics 4** property (Universal Analytics is retired)\n- A **Google Cloud Platform (GCP)** project with Analytics Data API enabled\n- A GCP Service Account JSON credential key\n\n---\n\n## Installation\n\nInstall the package via Composer:\n\n```bash\ncomposer require zfhassaan/genlytics\n```\n\n### Auto-Discovery\nGenlytics automatically registers its ServiceProvider (`zfhassaan\\genlytics\\provider\\AnalyticsServiceProvider`) and Facade alias (`Genlytics`). No manual registration in `config/app.php` is needed.\n\n---\n\n## Google Cloud & GA4 Setup\n\n1. **Enable the API**:\n   - Go to [Google Cloud Console](https://console.cloud.google.com/).\n   - Select your project and navigate to **APIs & Services** > **Library**.\n   - Search for **Google Analytics Data API** and click **Enable**.\n\n2. **Create Service Account**:\n   - Go to **IAM & Admin** > **Service Accounts**.\n   - Click **Create Service Account**, give it a name (e.g. `ga4-analytics-reader`), and click Create.\n   - Click the newly created service account, navigate to the **Keys** tab, click **Add Key** > **Create new key**, and choose **JSON**.\n   - Download the JSON file and save it securely in your Laravel app (e.g. `storage/app/analytics/service-account.json`).\n\n3. **Grant Access in Google Analytics**:\n   - Open [Google Analytics](https://analytics.google.com/).\n   - Go to **Admin** (gear icon) > **Property Access Management**.\n   - Click **+** > **Add users**.\n   - Enter your Service Account email address (e.g., `ga4-analytics-reader@your-project.iam.gserviceaccount.com`).\n   - Assign the **Viewer** or **Analyst** role and click **Add**.\n\n4. **Find your Property ID**:\n   - In Google Analytics Admin, go to **Property Settings** > **Property Details**.\n   - Copy the numeric **Property ID** (e.g., `123456789`).\n\n---\n\n## Configuration\n\nPublish the package configuration:\n\n```bash\nphp artisan vendor:publish --tag=genlytics-config\n```\n\nAdd the following environment variables to your `.env` file:\n\n```env\n# Required GA4 Settings\nGENLYTICS_PROPERTY_ID=123456789\nGENLYTICS_CREDENTIALS=storage/app/analytics/service-account.json\n\n# Performance & Caching (Recommended)\nGENLYTICS_ENABLE_CACHE=true\nGENLYTICS_CACHE_STORE=redis\nGENLYTICS_CACHE_PREFIX=genlytics\nGENLYTICS_CACHE_USE_TAGS=true\n\n# Background Queue Processing\nGENLYTICS_USE_BACKGROUND_JOBS=true\nGENLYTICS_QUEUE_CONNECTION=redis\nGENLYTICS_QUEUE_NAME=analytics\n```\n\n---\n\n## Your First Query\n\n```php\nuse zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;\n\n// Fetch top 5 countries by active users for the last 7 days:\n$data = Genlytics::getReport(\n    period: ['start_date' => '7daysAgo', 'end_date' => 'today'],\n    dimension: 'country',\n    metrics: 'activeUsers',\n    options: ['limit' => 5]\n);\n\n// Returns clean PHP array:\n// [\n//     ['dimensions' => ['country' => 'United States'], 'metrics' => ['activeUsers' => 1420]],\n//     ['dimensions' => ['country' => 'United Kingdom'], 'metrics' => ['activeUsers' => 840]],\n//     ...\n// ]\n```\n\n---\n\n## Running Test Suites & Code Coverage\n\nGenlytics is rigorously verified with **160 automated tests** and **484 assertions** across 19 test suites, ensuring **90.43% line code coverage** (100% on commands, events, API resources, exceptions, DTOs, background jobs, and listeners) with zero test failures.\n\nFor the comprehensive class-by-class verification report, see the [Test Coverage & Verification](test-coverage) section.\n\n### Test Execution Commands\n\nFrom the root project directory:\n\n```bash\n# 1. Run all 160 test cases across all test suites:\ncomposer test:genlytics\n# or directly:\nphp packages/zfhassaan/genlytics/test-runner.php\n\n# 2. Run with detailed test-dox format:\nphp packages/zfhassaan/genlytics/test-runner.php --testdox\n\n# 3. Run a specific test suite or method:\nphp packages/zfhassaan/genlytics/test-runner.php --filter QuotaManagerTest\nphp packages/zfhassaan/genlytics/test-runner.php --filter test_it_dispatches_realtime_broadcast\n\n# 4. Run test coverage with text report in console (90.4% coverage):\ncomposer test:genlytics:coverage\n# or directly:\nphp -d xdebug.mode=coverage packages/zfhassaan/genlytics/test-runner.php --coverage-text\n\n# 5. Generate interactive HTML coverage report in coverage/ directory:\ncomposer test:genlytics:coverage-html\n# or directly:\nphp -d xdebug.mode=coverage packages/zfhassaan/genlytics/test-runner.php --coverage-html coverage\n```\n\nIf working directly inside `packages/zfhassaan/genlytics`:\n\n```bash\n# Run tests:\ncomposer test\n\n# Run code coverage:\ncomposer run test:coverage\n\n# Generate HTML coverage report:\ncomposer run test:coverage-html\n```\n\n### Complete Test Suites Breakdown\n\n| Test Suite | Tests | Assertions | Focus Area | Status |\n| :--- | :--- | :--- | :--- | :--- |\n| **`GenlyticsTest`** | 20 | 54 | Facade methods, e-commerce funnels, quota drain, resource helpers | `100% PASS` |\n| **`QuotaManagerTest`** | 17 | 79 | Quota tracking, concurrency locks, PST math, operational modes | `100% PASS` |\n| **`CacheManagerTest`** | 15 | 26 | Configurable TTL per report type, cache tags, custom durations | `100% PASS` |\n| **`ResourcesTest`** | 12 | 49 | Package versioning (`1.1.0`), API resource wrappers, envelope formats | `100% PASS` |\n| **`AnalyticsServiceTest`** | 12 | 26 | Service orchestration, stale cache fallback, custom TTLs | `100% PASS` |\n| **`AnalyticsQueryBuilderTest`** | 11 | 27 | Fluent query builder, date scopes, casting to DTOs & API resources | `100% PASS` |\n| **`AnalyticsRepositoryTest`** | 11 | 13 | GA4 Data API client integration, concurrency slots, batch runs | `100% PASS` |\n| **`AnalyticsDTO`** | 9 | 26 | Typed DTO mappings for pages, countries, funnels, products | `100% PASS` |\n| **`FetchAnalyticsDataJobTest`** | 8 | 15 | Non-blocking async queue jobs, unique locks, and retry strategies | `100% PASS` |\n| **`DataTransformerTest`** | 8 | 46 | GA4 protobuf response transformation to clean arrays/JSON | `100% PASS` |\n| **`AnalyticsServiceProviderTest`** | 7 | 19 | Service provider bindings, singletons, published assets, aliases | `100% PASS` |\n| **`GenlyticsIntegrationTest`** | 7 | 10 | Laravel container bindings, facade registration, configuration | `100% PASS` |\n| **`ExceptionsTest`** | 6 | 13 | QuotaExceededException, QuotaValidationException, error envelopes | `100% PASS` |\n| **`AnalyticsEventsTest`** | 5 | 18 | Event dispatching (`QuotaUsageUpdated`, `AnalyticsCacheUpdated`) | `100% PASS` |\n| **`RefreshAnalyticsCacheTest`** | 4 | 9 | Artisan `genlytics:refresh-cache` and `--consume-all` flag | `100% PASS` |\n| **`ConsumeDailyQuotaTest`** | 3 | 12 | Artisan `genlytics:consume-quota` command, dry-run mode | `100% PASS` |\n| **`ServiceProviderTest`** | 3 | 6 | Service provider boot, config publishing, singleton instances | `100% PASS` |\n| **`QuotaStreamControllerTest`** | 2 | 10 | Real-time SSE streams and telemetry JSON resource endpoint | `100% PASS` |\n| **`UpdateRealTimeCacheTest`** | 2 | 4 | Automatic real-time background cache re-scheduling | `100% PASS` |\n| **`QuotaStatusDTOTest`** | 1 | 5 | Quota status serialization and remaining calculation | `100% PASS` |\n| **Total** | **160 Tests** | **484 Assertions** | **Full Package Coverage (90.43% lines)** | **100% PASS** |\n\n\n",
  "fluent-queries": "# Fluent Query Builder & Dedicated Helpers\n\nGenlytics provides an **Eloquent-like fluent query builder** and a suite of **dedicated helper functions**, making GA4 data fetching intuitive, readable, and developer-friendly without having to write raw Protobuf options.\n\n---\n\n## 1. Eloquent-Style Fluent Query Builder\n\nStart any custom query using `Genlytics::query()`:\n\n```php\nuse zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;\n\n// Build a custom query with method chaining:\n$countries = Genlytics::query()\n    ->last30Days()\n    ->dimensions('country', 'city')\n    ->metrics('activeUsers', 'sessions')\n    ->orderBy('activeUsers', 'DESC')\n    ->limit(10)\n    ->get(); // Returns an Illuminate\\Support\\Collection!\n\n// Process with standard Laravel Collection methods:\n$topCountry = $countries->first();\n$usUsers = $countries->where('dimensions.country', 'United States');\n```\n\n### Predefined Date Ranges\nNo need to format date strings manually:\n\n```php\nGenlytics::query()->today()->get();\nGenlytics::query()->yesterday()->get();\nGenlytics::query()->last7Days()->get();\nGenlytics::query()->last14Days()->get();\nGenlytics::query()->last30Days()->get();\nGenlytics::query()->last90Days()->get();\nGenlytics::query()->period('2026-01-01', '2026-06-30')->get();\n```\n\n### Chaining Dimensions & Metrics\n\n```php\n// Pass multiple as separate arguments or as an array:\nGenlytics::query()\n    ->dimensions('pagePath', 'pageTitle', 'deviceCategory')\n    ->metrics('screenPageViews', 'activeUsers', 'bounceRate')\n    ->limit(25)\n    ->offset(0)\n    ->get();\n```\n\n### Execution & Caching Helpers\n\n| Method | Return Type | Description |\n| :--- | :--- | :--- |\n| `->get()` | `Illuminate\\Support\\Collection` | Returns rows wrapped in an Eloquent-like Collection |\n| `->toArray()` | `array` | Returns pure PHP array (maximum speed & minimal memory) |\n| `->first()` | `?array` | Returns only the first row or null |\n| `->count()` | `int` | Returns count of rows |\n| `->toJson()` | `Illuminate\\Http\\JsonResponse` | Formats as JSON response for API controllers |\n| `->remember(int $ttl)` | `self` | Caches query result for specified integer seconds |\n| `->cacheFor($duration)` | `self` | Accepts integer seconds, `\\DateInterval`, or `\\DateTimeInterface` |\n| `->ttl($duration)` | `self` | Alias for `remember()` / `cacheFor()` |\n| `->dontCache()` | `self` | Bypasses cache entirely (fetches ground truth from GA4) |\n| `->noCache()` | `self` | Alias for `dontCache()` |\n| `->fresh()` | `self` | Bypasses cache and fetches fresh data directly from GA4 |\n\n### Query-Level Cache Lifetimes\nYou can fine-tune caching on every single query without changing global configuration:\n\n```php\n// Integer seconds (2 hours)\n$data = Genlytics::query()->last7Days()->dimensions('country')->metrics('activeUsers')->remember(7200)->get();\n\n// DateInterval (e.g., 45 minutes)\n$data = Genlytics::query()->today()->metric('screenPageViews')->cacheFor(new \\DateInterval('PT45M'))->get();\n\n// DateTime / Carbon (until end of today)\n$data = Genlytics::query()->dimension('deviceCategory')->ttl(now()->endOfDay())->get();\n\n// Force fresh query ground-truth\n$fresh = Genlytics::query()->last7Days()->metrics('activeUsers')->dontCache()->get();\n```\n\n---\n\n## 2. Dedicated One-Liner Query Methods\n\nFor the 90% of use cases where you need standard reports, Genlytics provides pre-optimized, dedicated methods:\n\n### Content & Pages\n```php\n// Top 10 viewed pages for the last 30 days:\n$pages = Genlytics::topPages(limit: 10, period: '30daysAgo');\n\n// Each row contains 'pagePath', 'pageTitle', 'screenPageViews', 'activeUsers', 'averageSessionDuration'\n```\n\n### Geography & Traffic\n```php\n// Top countries by active users:\n$countries = Genlytics::topCountries(limit: 10, period: '30daysAgo');\n\n// Top cities:\n$cities = Genlytics::topCities(limit: 10, period: '30daysAgo');\n\n// Acquisition channels & mediums:\n$sources = Genlytics::trafficSources(limit: 10, period: '30daysAgo');\n```\n\n### User Behavior & Technology\n```php\n// Overall engagement stats (newUsers, engagedSessions, engagementRate):\n$engagement = Genlytics::userEngagement(period: '30daysAgo');\n\n// Device categories (mobile, desktop, tablet):\n$devices = Genlytics::deviceCategories(period: '30daysAgo');\n\n// Top web browsers (Chrome, Safari, Firefox):\n$browsers = Genlytics::topBrowsers(limit: 5);\n\n// Conversions by event name:\n$events = Genlytics::conversions(limit: 10);\n```\n\n### Time Trends & Counts\n```php\n// Daily trend of active users over the last 30 days:\n$trend = Genlytics::dailyTrend(days: 30);\n\n// Total active users count as integer:\n$totalUsers = Genlytics::activeUsers(period: '30daysAgo'); // e.g. 14,250\n\n// Total sessions count as integer:\n$totalSessions = Genlytics::totalSessions(period: '30daysAgo'); // e.g. 21,800\n```\n\n---\n\n## 3. Dedicated Real-Time Helpers\n\nQuery active users on your site right now (last 30 minutes):\n\n```php\n// Count of active users right now:\n$liveUsers = Genlytics::realtimeActiveUsers(); // int\n\n// Top pages being viewed right now:\n$activePages = Genlytics::realtimeTopPages(limit: 5);\n\n// Top countries active right now:\n$activeCountries = Genlytics::realtimeTopCountries(limit: 5);\n```\n\n",
  "ecommerce-and-funnels": "# E-Commerce & Conversion Funnel Pipelines\n\nGenlytics provides first-class support for **E-Commerce Product Performance**, **Category Intelligence**, and **Step-by-Step Conversion Funnel Pipelines**. Easily analyze customer drop-off points, revenue drivers, and conversion velocity with zero boilerplate.\n\n---\n\n## 1. E-Commerce Purchase Funnel\n\nThe `Genlytics::ecommerceFunnel()` method analyzes the default Google Analytics 4 purchase journey:\n1. `view_item` (Product Discovery)\n2. `add_to_cart` (Cart Intent)\n3. `begin_checkout` (Checkout Initiation)\n4. `purchase` (Transaction Completion)\n\n```php\nuse zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;\n\n// Analyze funnel over the last 30 days\n$funnel = Genlytics::ecommerceFunnel('30daysAgo');\n\necho \"Funnel Name: {$funnel->name}\\n\";\necho \"Total Entries: {$funnel->totalEntries}\\n\";\necho \"Total Completions: {$funnel->totalCompletions}\\n\";\necho \"Overall Conversion Rate: {$funnel->overallConversionRate}%\\n\";\n\n// Iterate each sequential step in the pipeline\nforeach ($funnel->steps as $step) {\n    echo \"Step {$step->step}: {$step->name} ({$step->eventName})\\n\";\n    echo \" - Count: {$step->count}\\n\";\n    echo \" - Drop-Off: {$step->dropOffCount} ({$step->dropOffRate}%)\\n\";\n    echo \" - Step Conversion: {$step->conversionRate}%\\n\";\n    echo \" - Overall Conversion: {$step->overallConversionRate}%\\n\";\n}\n```\n\n### Response JSON Structure\n\n```json\n{\n  \"name\": \"E-Commerce Purchase Funnel\",\n  \"total_entries\": 14800,\n  \"total_completions\": 1200,\n  \"overall_conversion_rate\": 8.11,\n  \"steps\": [\n    {\n      \"step\": 1,\n      \"name\": \"1. View Item\",\n      \"event_name\": \"view_item\",\n      \"count\": 14800,\n      \"drop_off_count\": 0,\n      \"drop_off_rate\": 0.0,\n      \"conversion_rate\": 100.0,\n      \"overall_conversion_rate\": 100.0\n    },\n    {\n      \"step\": 2,\n      \"name\": \"2. Add to Cart\",\n      \"event_name\": \"add_to_cart\",\n      \"count\": 5700,\n      \"drop_off_count\": 9100,\n      \"drop_off_rate\": 61.49,\n      \"conversion_rate\": 38.51,\n      \"overall_conversion_rate\": 38.51\n    },\n    {\n      \"step\": 3,\n      \"name\": \"3. Begin Checkout\",\n      \"event_name\": \"begin_checkout\",\n      \"count\": 2400,\n      \"drop_off_count\": 3300,\n      \"drop_off_rate\": 57.89,\n      \"conversion_rate\": 42.11,\n      \"overall_conversion_rate\": 16.22\n    },\n    {\n      \"step\": 4,\n      \"name\": \"4. Purchase\",\n      \"event_name\": \"purchase\",\n      \"count\": 1200,\n      \"drop_off_count\": 1200,\n      \"drop_off_rate\": 50.0,\n      \"conversion_rate\": 50.0,\n      \"overall_conversion_rate\": 8.11\n    }\n  ]\n}\n```\n\n---\n\n## 2. Custom Conversion Funnels\n\nYou can define any arbitrary multi-step event sequence using `Genlytics::funnel()`:\n\n```php\n// Define custom SaaS onboarding pipeline\n$saasFunnel = Genlytics::funnel([\n    'Landing Page'   => 'page_view',\n    'Sign Up Started' => 'sign_up_start',\n    'Account Created' => 'account_created',\n    'Subscribed'      => 'subscription_created',\n], name: 'SaaS Onboarding Funnel', period: 'last30Days');\n```\n\n---\n\n## 3. Top Performing Products\n\nQuery products ranked by gross revenue, units sold, view volume, and cart-to-view rate. Each row is hydrated as a strongly-typed `ProductPerformanceDTO`:\n\n```php\nuse zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;\n\n// Get top 10 products\n$products = Genlytics::topProducts(limit: 10, period: '30daysAgo');\n\nforeach ($products as $product) {\n    echo \"SKU: {$product->id}\\n\";\n    echo \"Name: {$product->name}\\n\";\n    echo \"Category: {$product->category}\\n\";\n    echo \"Revenue: \\${$product->revenue}\\n\";\n    echo \"Purchased: {$product->itemsPurchased} units\\n\";\n    echo \"Cart-to-View Rate: {$product->cartToViewRate}%\\n\";\n}\n```\n\n### ProductPerformanceDTO Properties\n\n| Property | Type | Description |\n|---|---|---|\n| `id` | `string` | GA4 `itemId` (SKU or identifier) |\n| `name` | `string` | GA4 `itemName` |\n| `category` | `string` | GA4 `itemCategory` |\n| `revenue` | `float` | Total monetary gross item revenue |\n| `itemsPurchased` | `int` | Total units purchased |\n| `itemsViewed` | `int` | Total product detail page impressions |\n| `itemsAddedToCart` | `int` | Total units added to shopping bag |\n| `cartToViewRate` | `float` | Percentage of views that generated cart additions |\n| `purchaseToViewRate` | `float` | Percentage of views that completed checkout |\n\n---\n\n## 4. Top Performing Product Categories\n\nAnalyze revenue distribution across store categories using `Genlytics::topCategories()`:\n\n```php\nuse zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;\n\n// Get top 5 product categories\n$categories = Genlytics::topCategories(limit: 5, period: '30daysAgo');\n\nforeach ($categories as $cat) {\n    echo \"Category: {$cat->category}\\n\";\n    echo \"Revenue: \\${$cat->revenue}\\n\";\n    echo \"Units Sold: {$cat->itemsPurchased}\\n\";\n    echo \"Cart-to-View Rate: {$cat->cartToViewRate}%\\n\";\n}\n```\n\n---\n\n## 5. Standardized Laravel API Resources\n\nAll e-commerce and funnel DTOs seamlessly integrate with Laravel API Resources for clean controller responses:\n\n```php\nnamespace App\\Http\\Controllers\\Api;\n\nuse App\\Http\\Controllers\\Controller;\nuse zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;\nuse zfhassaan\\genlytics\\Http\\Resources\\ProductPerformanceResource;\nuse zfhassaan\\genlytics\\Http\\Resources\\FunnelPipelineResource;\n\nclass AnalyticsReportController extends Controller\n{\n    public function funnel()\n    {\n        $funnel = Genlytics::ecommerceFunnel('last30Days');\n        return new FunnelPipelineResource($funnel);\n    }\n\n    public function products()\n    {\n        $products = Genlytics::topProducts(limit: 15);\n        return ProductPerformanceResource::collection($products);\n    }\n}\n```\n\n",
  "quota-and-limits": "# Google Analytics 4 (GA4) API Limits & Maximum Utilization\n\nGenlytics features an enterprise-grade **Quota & Adaptive Resource Pacing Engine** built specifically for the Google Analytics Data API (GA4) quota specifications.\n\nIt protects your application from `429 Too Many Requests` or `RESOURCE_EXHAUSTED` errors while unlocking **maximum utilization of your daily API budget**.\n\n---\n\n## GA4 Data API Quota Specifications\n\nGenlytics enforces and tracks all official GA4 Data API quota limits:\n\n| Quota Dimension | Standard (Free) Property | Analytics 360 Property | Reset Window |\n| :--- | :--- | :--- | :--- |\n| **Core Tokens Per Day** | **200,000 tokens** | **2,000,000 tokens** | Midnight Pacific Time (**PST**) |\n| **Core Tokens Per Hour** | **40,000 tokens** | **40,000 tokens** | Rolling / Top-of-hour |\n| **Concurrent Requests** | **10 requests** | **10 requests** | Instantaneous |\n| **Project-Level Requests** | **50,000 requests / day** | **50,000 requests / day** | Midnight PST |\n| **IP-Level Quota** | **10 QPS** (queries / sec) | **10 QPS** | 1-second sliding window |\n| **User-Level Quota** | **100 requests / 100s** | **100 requests / 100s** | 100-second window (up to 1,000) |\n\n> [!IMPORTANT]\n> **Midnight PST Reset Schedule**: All Google Analytics daily quotas refresh at **12:00:00 AM Pacific Standard Time (`America/Los_Angeles`)**. Genlytics automatically calculates seconds remaining until midnight PST and synchronizes cache TTLs with this exact schedule.\n\n---\n\n## Maximum Resource Utilization Architecture\n\nWithout intelligent pacing, an active analytics dashboard can burn through the 40,000 tokens/hour limit in 10 minutes, freezing API access for the remainder of the hour.\n\nGenlytics solves this through a 4-tier optimization engine:\n\n```\n                  ┌──────────────────────────────────────────────┐\n                  │          Incoming Analytics Request          │\n                  └──────────────────────┬───────────────────────┘\n                                         ▼\n                  ┌──────────────────────────────────────────────┐\n                  │ 1. Multi-Tier Check (IP QPS & User 100s)     │\n                  └──────────────────────┬───────────────────────┘\n                                         ▼\n                  ┌──────────────────────────────────────────────┐\n                  │ 2. Concurrency Semaphore (Max 10 In-Flight)  │\n                  └──────────────────────┬───────────────────────┘\n                                         ▼\n                  ┌──────────────────────────────────────────────┐\n                  │ 3. Adaptive Token Pacing (Hours Remaining)   │\n                  └──────────────────────┬───────────────────────┘\n                                         ▼\n            ┌────────────────────────────┴───────────────────────────┐\n            ▼                                                        ▼\n    [Quota Optimal]                                        [Quota >85% Consumed]\n  Direct GA4 Execution                                    Serve SWR Cached Stale\n  Sync Google Ground Truth                                Reserve Tokens for High-Pri\n```\n\n### 1. Adaptive Budget Pacing\nGenlytics calculates your sustainable burn rate dynamically:\n$$\\text{Target Hourly Budget} = \\frac{\\text{Remaining Daily Tokens}}{\\text{Hours Remaining Until Midnight PST}}$$\nIf consumption exceeds 1.5× the sustainable burn rate and approaches the hourly threshold, Genlytics transitions from `optimal` to `pacing_required`, transparently serving stale cached data for read queries while safeguarding interactive requests.\n\n### 2. Atomic Concurrency Semaphore (Max 10)\nPrevents bursting beyond Google's hard limit of **10 concurrent requests per property**. If 10 requests are currently in flight, the 11th request:\n1. Micro-waits with exponential jitter backoff (20ms – 50ms).\n2. If slots remain saturated, returns fresh/stale SWR cache rather than throwing a `429` error.\n\n### 3. Bi-Directional Ground Truth Sync\nEvery response returned by the GA4 API includes `PropertyQuota` metadata. Genlytics parses `$response->getPropertyQuota()` in real time:\n- `$quota->getTokensPerDay()->getRemaining()`\n- `$quota->getTokensPerHour()->getRemaining()`\n- `$quota->getConcurrentRequests()->getRemaining()`\n\nThis ensures Genlytics internal counters never drift from Google's actual server-side counters.\n\n---\n\n## Developer API & Usage\n\n### 1. Check Current Quota & Pacing Status\n\n```php\nuse zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;\n\n// Get full typed QuotaStatusDTO\n$status = Genlytics::quotaStatus();\n\necho \"Daily Remaining: \" . $status->dailyTokensRemaining . \" / \" . $status->dailyTokensLimit;\necho \"Hourly Remaining: \" . $status->hourlyTokensRemaining . \" / \" . $status->hourlyTokensLimit;\necho \"Active Concurrent Requests: \" . $status->concurrentRequestsActive . \" / \" . $status->concurrentRequestsLimit;\necho \"PST Midnight Reset In: \" . $status->secondsUntilDailyReset . \" seconds\";\necho \"Pacing Status: \" . $status->pacingStatus; // 'optimal', 'pacing_required', 'critical_throttle'\n```\n\n### 2. Get Quick Remaining Tokens\n\n```php\n$remainingDaily = Genlytics::remainingTokens(); // e.g. 184520\n```\n\n### 3. Exposing Quota in API Responses\n\nReturn quota status to frontend dashboards using the standardized `QuotaStatusResource`:\n\n```php\nuse zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;\nuse zfhassaan\\genlytics\\Http\\Resources\\QuotaStatusResource;\n\nRoute::get('/api/analytics/quota', function () {\n    return new QuotaStatusResource(Genlytics::quotaStatus());\n});\n```\n\n**JSON Output:**\n```json\n{\n  \"data\": {\n    \"property_type\": \"standard\",\n    \"daily_tokens\": {\n      \"limit\": 200000,\n      \"consumed\": 15480,\n      \"remaining\": 184520,\n      \"utilization_percent\": 7.74\n    },\n    \"hourly_tokens\": {\n      \"limit\": 40000,\n      \"consumed\": 3200,\n      \"remaining\": 36800,\n      \"utilization_percent\": 8.0\n    },\n    \"concurrency\": {\n      \"active\": 2,\n      \"limit\": 10\n    },\n    \"project_requests\": {\n      \"limit\": 50000,\n      \"consumed\": 642,\n      \"utilization_percent\": 1.28\n    },\n    \"reset_schedule\": {\n      \"seconds_remaining\": 38420,\n      \"midnight_pst\": \"00:00:00 PST\"\n    },\n    \"pacing\": {\n      \"is_throttled\": false,\n      \"status\": \"optimal\"\n    }\n  }\n}\n```\n\n---\n\n## Consuming 100% Daily Quota (`consume_all`)\n\nIf your application needs to **consume all available daily quota** (e.g. daily pre-warming of all reports, nightly data warehouse exports, or exhaustive funnel breakdowns) so that zero free Google quota is wasted before the midnight PST reset:\n\n### 1. Activating 100% Daily Consumption\n\n#### Globally in `config/analytics.php`:\n```php\n'quota' => [\n    'mode'                    => 'consume_all', // Or 'maximum_utilization'\n    'consume_all_daily_quota' => true,          // Consumes up to 100% of 200,000 / 2,000,000 tokens\n],\n```\n\n#### At Runtime via Facade:\n```php\nuse zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;\n\n// Enable 100% daily quota consumption\nGenlytics::enableAllQuotaConsumption();\n\n// Check if all-quota mode is active\nif (Genlytics::isAllQuotaConsumptionEnabled()) {\n    // Queries will never be diverted to stale cache until 0 tokens remain\n}\n```\n\n#### Per-Query with Builder:\n```php\n$report = Genlytics::query()\n    ->last30Days()\n    ->dimensions(['country', 'city', 'pagePath'])\n    ->metrics(['activeUsers', 'sessions', 'screenPageViews'])\n    ->consumeAllQuota() // Zero throttling; uses all remaining tokens\n    ->get();\n```\n\n### 2. Automated Daily Quota Drainage & Cache Pre-Warming\n\nGenlytics provides a dedicated console command to proactively burn/extract remaining tokens into pre-warmed cache before the midnight PST reset:\n\n```bash\n# Proactively pre-warm cache and extract up to 100% daily quota\nphp artisan genlytics:consume-quota --target-utilization=100\n\n# Dry-run preview of tokens needed without hitting Google API\nphp artisan genlytics:consume-quota --dry-run\n\n# Or via refresh-cache with the --consume-all flag\nphp artisan genlytics:refresh-cache --consume-all\n```\n\n---\n\n## Real-Time Quota Telemetry & Live Updates\n\nGenlytics provides out-of-the-box real-time telemetry so your dashboards and administrative interfaces can monitor quota depletion live without polling.\n\n### 1. Laravel Echo / WebSockets (`QuotaUsageUpdated`)\n\nWhenever queries execute or ground-truth synchronizes, Genlytics fires `QuotaUsageUpdated` implementing `ShouldBroadcastNow`:\n\n```javascript\n// Listen with Laravel Echo (Reverb, Pusher, Soketi)\nEcho.channel('genlytics-quota')\n    .listen('.quota.updated', (event) => {\n        console.log(`Daily Tokens Remaining: ${event.daily_tokens_remaining}`);\n        console.log(`Hourly Consumed: ${event.hourly_tokens_consumed} / 40,000`);\n        console.log(`Active Concurrency: ${event.concurrent_active} / 10`);\n        console.log(`Pacing Status: ${event.pacing_status}`);\n        console.log(`PST Reset Countdown: ${event.seconds_until_pst_reset}s`);\n    });\n```\n\n### 2. Server-Sent Events (SSE) Stream\n\nFor lightweight HTTP streaming without WebSocket servers:\n\n```javascript\nconst evtSource = new EventSource('/genlytics/api/quota/stream');\n\nevtSource.addEventListener('quota.updated', (event) => {\n    const data = JSON.parse(event.data);\n    updateQuotaGauge(data.daily_tokens.remaining, data.daily_tokens.utilization_percent);\n});\n```\n\n---\n\n## Maximum Quota Utilization Mode (`maximum_utilization`)\n\nWhen you want to **extract maximum utility from your Google Analytics 4 API quota** (e.g. for high-volume enterprise ETL, data warehouse syncing, or dense executive dashboards), Genlytics provides a dedicated **Maximum Quota Utilization Mode**:\n\n### Key Features of Maximum Quota Mode:\n1. **Unlocks 100% of Token Capacity**: Relaxes early warning thresholds from 85% to 99%, allowing full token consumption without premature stale-cache intervention.\n2. **Full Concurrency Throughput**: Maximizes all 10 concurrent request slots with non-blocking spinwait queueing.\n3. **GCP User Quota Expansion**: Allows configuring user rate limits up to **1,000 requests per 100 seconds** (the maximum ceiling permitted by Google Analytics in Google Cloud Console).\n4. **Pre-Flight Validation**: Calculates estimated token cost before firing queries and validates against Google API constraints.\n\n### Enabling Maximum Quota Utilization\n\n#### 1. Globally in `config/analytics.php`:\n```php\n'quota' => [\n    'mode' => 'maximum_utilization', // 'maximum_utilization', 'balanced', or 'conservative'\n    'property_type' => 'standard',   // 200,000 tokens/day (or '360' for 2,000,000 tokens/day)\n    'user_requests_per_100s' => 1000,// Maximum permitted by Google Cloud\n],\n```\n\n#### 2. At Runtime via Facade:\n```php\nuse zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;\n\n// Enable maximum quota burst at runtime\nGenlytics::enableMaximumQuotaUtilization();\n\n// Or switch modes dynamically\nGenlytics::setQuotaMode('maximum_utilization');\n```\n\n#### 3. Per-Query with Eloquent Builder:\n```php\n$report = Genlytics::query()\n    ->last30Days()\n    ->dimensions(['country', 'city', 'pagePath'])\n    ->metrics(['activeUsers', 'sessions', 'screenPageViews'])\n    ->maxQuota() // Unlocks maximum quota utilization for this execution\n    ->get();\n```\n\n---\n\n## Quota & Configuration Validations\n\nGenlytics enforces rigorous validation checks on both configuration and runtime query execution to ensure your application stays compliant with Google Cloud policies:\n\n### 1. Configuration Validation Rules\n\n| Setting | Constraint | Error Thrown |\n| :--- | :--- | :--- |\n| `property_type` | Must be `'standard'` or `'360'` | `QuotaValidationException` (422) |\n| `mode` | Must be `'maximum_utilization'`, `'balanced'`, or `'conservative'` | `QuotaValidationException` (422) |\n| `concurrent_requests_limit` | Must be between `1` and `10` (Google hard cap) | `QuotaValidationException` (422) |\n| `hourly_token_limit` | Cannot exceed `40,000` (Google hard cap) | `QuotaValidationException` (422) |\n| `project_daily_request_limit` | Cannot exceed `50,000` requests/day | `QuotaValidationException` (422) |\n| `ip_qps_limit` | Cannot exceed `10` queries/second | `QuotaValidationException` (422) |\n| `user_requests_per_100s` | Must be between `1` and `1,000` | `QuotaValidationException` (422) |\n\n### 2. Pre-Flight Query Validation\n\n```php\ntry {\n    // Validates query token budget before calling Google API\n    Genlytics::validateQuota($estimatedTokens = 5);\n} catch (QuotaExceededException $e) {\n    Log::warning(\"Quota check failed: \" . $e->getMessage());\n    echo \"Resets in \" . $e->getResetInSeconds() . \" seconds (Midnight PST)\";\n}\n```\n\n---\n\n## Configuration (`config/analytics.php`)\n\n```php\n'quota' => [\n    // Operational Mode: 'consume_all', 'maximum_utilization', 'balanced', 'conservative'\n    'mode'                       => env('GENLYTICS_QUOTA_MODE', 'balanced'),\n\n    // 'standard' (200,000) or '360' (2,000,000)\n    'property_type'              => env('GENLYTICS_PROPERTY_TYPE', 'standard'),\n\n    // Core limits\n    'daily_token_limit'          => env('GENLYTICS_DAILY_TOKEN_LIMIT', 200000),\n    'hourly_token_limit'         => env('GENLYTICS_HOURLY_TOKEN_LIMIT', 40000),\n    'concurrent_requests_limit'  => env('GENLYTICS_CONCURRENT_LIMIT', 10),\n    'project_daily_request_limit'=> env('GENLYTICS_PROJECT_DAILY_LIMIT', 50000),\n    'ip_qps_limit'               => env('GENLYTICS_IP_QPS_LIMIT', 10),\n    'user_requests_per_100s'     => env('GENLYTICS_USER_100S_LIMIT', 1000),\n\n    // PST Reset\n    'timezone'                   => 'America/Los_Angeles',\n\n    // Threshold where SWR cache takes over (1.0 for consume_all, 0.99 for maximum_utilization, 0.85 for balanced)\n    'warning_threshold'          => 0.85,\n\n    // Strategy when exhausted: 'cache_fallback' or 'strict'\n    'exhaustion_strategy'        => env('GENLYTICS_QUOTA_STRATEGY', 'cache_fallback'),\n\n    // Option to Consume All Daily Quota\n    'consume_all_daily_quota'    => env('GENLYTICS_CONSUME_ALL_QUOTA', false),\n\n    // Real-Time Telemetry Broadcasting\n    'realtime_broadcasting'      => env('GENLYTICS_REALTIME_QUOTA_BROADCAST', true),\n    'broadcast_channel'          => env('GENLYTICS_QUOTA_CHANNEL', 'genlytics-quota'),\n],\n```\n\n---\n\n## Automated Test Coverage & Verification\n\nGenlytics includes comprehensive test suites covering all quota algorithms, concurrency locks, PST timezone mathematics, operational modes, and real-time broadcasting:\n\n### 1. Test Suite Summary\n\n| Test Suite | Tests | Assertions | Status | Coverage Scope |\n| :--- | :--- | :--- | :--- | :--- |\n| **`GenlyticsTest`** | 20 | 54 | `100% PASS` | Facade methods, e-commerce funnels, quota drainage, resource helpers |\n| **`QuotaManagerTest`** | 17 | 79 | `100% PASS` | Quota tracking, modes (`consume_all`, `maximum_utilization`), PST math, concurrency locks, validation exceptions |\n| **`CacheManagerTest`** | 15 | 26 | `100% PASS` | Configurable TTL per report type, cache tags, custom durations |\n| **`ResourcesTest`** | 12 | 49 | `100% PASS` | Package versioning (`1.1.0`), typed API resource envelopes, JSON responses |\n| **`AnalyticsServiceTest`** | 12 | 26 | `100% PASS` | Service orchestration, stale cache fallback, custom TTLs |\n| **`AnalyticsQueryBuilderTest`** | 11 | 27 | `100% PASS` | Fluent chaining, `->consumeAllQuota()`, `->maxQuota()`, resource casting |\n| **`AnalyticsRepositoryTest`** | 11 | 13 | `100% PASS` | GA4 Data API client integration, concurrency slots, batch runs |\n| **`AnalyticsDTO`** | 9 | 26 | `100% PASS` | Strongly-typed DTO conversions for pages, countries, funnels, products |\n| **`FetchAnalyticsDataJobTest`** | 8 | 15 | `100% PASS` | Non-blocking async queue jobs, unique locks, and retry strategies |\n| **`DataTransformerTest`** | 8 | 46 | `100% PASS` | GA4 protobuf response transformation to clean arrays/JSON |\n| **`AnalyticsServiceProviderTest`** | 7 | 19 | `100% PASS` | Service provider boot, config publishing, singleton instances, aliases |\n| **`GenlyticsIntegrationTest`** | 7 | 10 | `100% PASS` | Laravel container bindings, facade registration, configuration |\n| **`ExceptionsTest`** | 6 | 13 | `100% PASS` | QuotaExceededException, QuotaValidationException, error envelopes |\n| **`AnalyticsEventsTest`** | 5 | 18 | `100% PASS` | `QuotaUsageUpdated` broadcast payload, channel routing, cache events |\n| **`RefreshAnalyticsCacheTest`** | 4 | 9 | `100% PASS` | Cache clear, refresh specific types, `--consume-all` flag |\n| **`ConsumeDailyQuotaTest`** | 3 | 12 | `100% PASS` | Artisan `genlytics:consume-quota` command, dry-run, target utilization |\n| **`ServiceProviderTest`** | 3 | 6 | `100% PASS` | Service provider boot, config publishing, singleton instances |\n| **`QuotaStreamControllerTest`** | 2 | 10 | `100% PASS` | SSE stream headers, JSON telemetry endpoint |\n| **`UpdateRealTimeCacheTest`** | 2 | 4 | `100% PASS` | Automatic real-time background cache re-scheduling |\n| **`QuotaStatusDTOTest`** | 1 | 5 | `100% PASS` | DTO serialization, utilization percentages, pacing states |\n| **Total Suite** | **160 Tests** | **484 Assertions** | `100% PASS` | **Full Package Code Coverage (90.43% lines)** |\n\n### 2. Running Test Coverage Commands\n\n```bash\n# Run complete test suite (160 tests, 484 assertions):\ncomposer test:genlytics\n# or:\nphp packages/zfhassaan/genlytics/test-runner.php\n\n# Run with full line code coverage text summary:\ncomposer test:genlytics:coverage\n# or:\nphp -d xdebug.mode=coverage packages/zfhassaan/genlytics/test-runner.php --coverage-text\n\n# Generate HTML code coverage report:\ncomposer test:genlytics:coverage-html\n# or:\nphp -d xdebug.mode=coverage packages/zfhassaan/genlytics/test-runner.php --coverage-html coverage\n```\n\n### 3. Testing Real-Time Quota Broadcasting in Laravel\n\n```php\nuse Illuminate\\Support\\Facades\\Event;\nuse zfhassaan\\genlytics\\Events\\QuotaUsageUpdated;\nuse zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;\n\npublic function test_it_dispatches_realtime_broadcast_on_query()\n{\n    Event::fake([QuotaUsageUpdated::class]);\n\n    Genlytics::query()->today()->metric('activeUsers')->get();\n\n    Event::assertDispatched(QuotaUsageUpdated::class, function ($event) {\n        return $event->tokensConsumed >= 1\n            && $event->channel === 'genlytics-quota'\n            && $event->status->dailyTokensRemaining > 0;\n    });\n}\n```\n\n### 3. Testing Artisan Quota Commands\n\n```php\n// Test dry-run token estimation\n$this->artisan('genlytics:consume-quota', ['--dry-run' => true])\n    ->expectsOutput('DRY RUN: Pre-flight estimation complete. Exiting without calling Google API.')\n    ->assertExitCode(0);\n\n// Test --consume-all cache pre-warming\n$this->artisan('genlytics:refresh-cache', ['--consume-all' => true])\n    ->expectsOutput('🚀 Activating Full Daily Quota Consumption Mode...')\n    ->assertExitCode(0);\n```\n\n",
  "dtos-and-resources": "# Response DTOs & Standardized API Resources\n\nGenlytics provides a rich suite of **Data Transfer Objects (DTOs)** and **Laravel API Resources** (`JsonResource`), bringing strict type-safety, IDE autocompletion, embedded package versioning, and standardized JSON API envelopes to all your Google Analytics data.\n\n---\n\n## 1. Why Use Response DTOs?\n\nRaw analytics data is traditionally returned as loose associative arrays:\n```php\n// Unsafe & prone to typos:\n$views = $row['metrics']['screenPageViews']; // If misspelled as 'screenViews', silent bug!\n```\n\nWith Genlytics DTOs, responses are strongly typed with IDE autocomplete:\n```php\n// Type-safe & autocompleted:\n$dto = PageReportDTO::fromRow($row);\necho $dto->views;       // (int) 5420\necho $dto->path;        // (string) \"/pricing\"\necho $dto->activeUsers; // (int) 1200\n```\n\n---\n\n## 2. Available DTO Classes\n\nLocated in `zfhassaan\\genlytics\\DTOs`:\n\n| DTO Class | Key Properties | Ideal Use Case |\n| :--- | :--- | :--- |\n| `AnalyticsRowDTO` | `$dimensions`, `$metrics`, `getIntMetric()`, `getFloatMetric()` | Generic queries with dynamic dimensions & metrics |\n| `PageReportDTO` | `$path`, `$title`, `$views`, `$activeUsers`, `$averageDuration` | Top URLs, blog posts, landing page metrics |\n| `CountryReportDTO` | `$country`, `$activeUsers`, `$sessions` | Geographic distribution & international traffic |\n| `TrafficSourceDTO` | `$source`, `$medium`, `$sessions`, `$activeUsers`, `$bounceRate` | Marketing attribution & acquisition channels |\n| `ProductPerformanceDTO`| `$id`, `$name`, `$category`, `$revenue`, `$itemsPurchased`, `$cartToViewRate` | E-commerce items & sales performance |\n| `CategoryPerformanceDTO`| `$category`, `$revenue`, `$units`, `$itemsViewed`, `$cartToViewRate` | Store product category volume & sales |\n| `FunnelPipelineDTO` | `$name`, `$totalEntries`, `$totalCompletions`, `$overallConversionRate`, `$steps` | Sequential conversion & drop-off analytics |\n| `QuotaStatusDTO` | `$dailyTokensRemaining`, `$hourlyTokensRemaining`, `$pacingStatus`, `$mode` | Live GA4 quota monitoring telemetry |\n| `UserEngagementDTO` | `$activeUsers`, `$newUsers`, `$engagedSessions`, `$engagementRate` | High-level executive KPI dashboards |\n| `DeviceCategoryDTO` | `$category`, `$activeUsers`, `$sessions` | Mobile vs Desktop responsive design analytics |\n\n---\n\n## 3. Query Builder DTO & Resource Integration\n\nThe `AnalyticsQueryBuilder` features instant DTO and API Resource casting methods:\n\n```php\nuse zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;\n\n// Cast to generic AnalyticsRowDTOs:\n$rows = Genlytics::query()->last7Days()->dimensions('country')->metrics('activeUsers')->toDto();\n\n// Cast directly to typed DTOs:\n$pages = Genlytics::query()->last30Days()->dimensions('pagePath', 'pageTitle')->metrics('screenPageViews', 'activeUsers')->toPageDto();\n$countries = Genlytics::query()->last30Days()->dimension('country')->metrics('activeUsers', 'sessions')->toCountryDto();\n$products = Genlytics::query()->last30Days()->dimensions('itemId', 'itemName')->toProductDto();\n\n// Cast directly to Laravel API Resources (with package version envelope):\n$pageResources = Genlytics::query()->last30Days()->toPageResource();\n$countryResources = Genlytics::query()->last30Days()->toCountryResource();\n$productResources = Genlytics::query()->last30Days()->toProductResource();\n\n// Return standardized HTTP JsonResponse directly from controller:\nreturn Genlytics::query()->last7Days()->toResponse();\n```\n\n---\n\n## 4. Standardized API Resources with Embedded Package Version\n\nAll Genlytics API Resources implement `HasGenlyticsVersion`. When returned from Laravel controllers or serialized to JSON, they automatically include the active **package version** (`1.1.0`), success status, count, and ISO 8601 timestamps.\n\n### Output JSON Format:\n```json\n{\n  \"success\": true,\n  \"version\": \"1.1.0\",\n  \"data\": [\n    {\n      \"dimensions\": {\n        \"country\": \"United States\",\n        \"city\": \"New York\"\n      },\n      \"metrics\": {\n        \"activeUsers\": \"1420\",\n        \"sessions\": \"1890\"\n      }\n    }\n  ],\n  \"meta\": {\n    \"version\": \"1.1.0\",\n    \"count\": 1,\n    \"timestamp\": \"2026-10-25T10:40:07+00:00\"\n  }\n}\n```\n\n---\n\n## 5. Instant Controller One-Liners via Facade\n\nEvery major query function in `Genlytics` provides a corresponding `*Resource()` method that returns an API Resource ready for controller output:\n\n```php\nnamespace App\\Http\\Controllers\\Api;\n\nuse App\\Http\\Controllers\\Controller;\nuse zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;\n\nclass AnalyticsApiController extends Controller\n{\n    // Return Top Pages with version 1.1.0 envelope:\n    public function pages()\n    {\n        return Genlytics::topPagesResource(limit: 10);\n    }\n\n    // Return Top Countries:\n    public function countries()\n    {\n        return Genlytics::topCountriesResource(limit: 10);\n    }\n\n    // Return Top Products:\n    public function products()\n    {\n        return Genlytics::topProductsResource(limit: 10);\n    }\n\n    // Return E-Commerce Funnel Pipeline:\n    public function funnel()\n    {\n        return Genlytics::ecommerceFunnelResource();\n    }\n\n    // Return Live Quota Telemetry:\n    public function quota()\n    {\n        return Genlytics::quotaStatusResource();\n    }\n}\n```\n",
  "architecture": "# Architecture & SOLID Principles\n\nGenlytics is architected strictly around **SOLID principles**, the **Repository pattern**, and an **Event-driven design**.\n\n---\n\n## High-Level Architecture Overview\n\n```\n[ Your Application / Controller / Console / Job ]\n                       │\n                       ▼\n             [ Genlytics Facade ]\n                       │\n                       ▼\n            [ AnalyticsService ] (Orchestrator & SWR Caching)\n            ┌──────────┴──────────────┬──────────────────┐\n            ▼                         ▼                  ▼\n[ AnalyticsRepositoryInterface ] [ CacheManagerInterface ] [ DataTransformerInterface ]\n            │                         │                  │\n            ▼                         ▼                  ▼\n[ AnalyticsRepository (GA4 API) ] [ CacheManager (Redis) ] [ DataTransformer (Array) ]\n```\n\n---\n\n## SOLID Principles in Practice\n\n### 1. Single Responsibility Principle (SRP)\nEach class has exactly one clear reason to change:\n- **`AnalyticsRepository`**: Focuses 100% on communication with the Google Analytics Data API, converting parameters into protobuf requests (`DateRange`, `Dimension`, `Metric`).\n- **`CacheManager`**: Dedicated to cache storage, granular TTLs, tagging, key generation, and distributed atomic locks.\n- **`DataTransformer`**: Transforms complex Google API Protobuf responses into clean, predictable PHP arrays or JSON-ready structures.\n- **`AnalyticsService`**: Orchestrates data access, cache verification, lock checks, and event dispatches.\n- **`Genlytics`**: Client-facing adapter providing seamless Facade access and backward compatibility.\n\n### 2. Open/Closed Principle (OCP)\nClasses are open for extension without modifying existing contracts. For example, the addition of GA4's `batchRunReports` seamlessly extends the repository and service contracts without touching single-report execution code paths.\n\n### 3. Liskov Substitution Principle (LSP)\nAll high-level services interact strictly through interfaces:\n- `AnalyticsRepositoryInterface`\n- `CacheManagerInterface`\n- `DataTransformerInterface`\n\nAny custom implementation (for instance, an in-memory test repository or a custom database cache driver) can be substituted in Laravel's service container without breaking any callers.\n\n### 4. Interface Segregation Principle (ISP)\nInterfaces are small, cohesive, and client-specific. Callers only depend on the methods they actually consume.\n\n### 5. Dependency Inversion Principle (DIP)\nDependencies are injected through abstractions rather than concretions. `AnalyticsServiceProvider` binds all implementations into the Laravel IoC container:\n\n```php\n$this->app->singleton(AnalyticsRepositoryInterface::class, function ($app) {\n    ...\n    return new AnalyticsRepository($client, $propertyId);\n});\n```\n\n---\n\n## Event-Driven Architecture\n\nGenlytics emits lifecycle events throughout the query flow:\n\n| Event | Fired When | Payload |\n| :--- | :--- | :--- |\n| `AnalyticsDataRequested` | A report query is initiated | `$type`, `$parameters`, `$forceRefresh` |\n| `AnalyticsDataFetched` | Data is fetched (from cache or API) | `$type`, `$data`, `$parameters`, `$fromCache` |\n| `AnalyticsCacheUpdated` | Data is successfully saved to cache | `$cacheKey`, `$type`, `$data`, `$parameters` |\n| `AnalyticsQueryFailed` | An API or processing error occurs | `$type`, `$parameters`, `$exception` |\n\n### Listening to Events\n\nYou can attach listeners to track analytics usage, alert on API quota errors, or stream real-time metrics over WebSockets:\n\n```php\nuse zfhassaan\\genlytics\\Events\\AnalyticsDataFetched;\n\nEvent::listen(AnalyticsDataFetched::class, function (AnalyticsDataFetched $event) {\n    Log::info(\"Analytics query complete\", [\n        'type' => $event->type,\n        'from_cache' => $event->fromCache,\n    ]);\n});\n```\n\n",
  "configuration": "# Configuration & Performance Tuning\n\nGenlytics provides enterprise-grade configuration options for high-throughput applications, allowing complete control over cache drivers, background queues, TTLs, and network transports.\n\n---\n\n## Configuration Reference (`config/analytics.php`)\n\n```php\nreturn [\n\n    // Numeric GA4 Property ID\n    'property_id' => env('GENLYTICS_PROPERTY_ID'),\n\n    // Path to Service Account JSON or credentials array\n    'service_account_credentials_json' => env('GENLYTICS_CREDENTIALS'),\n\n    // Network transport ('rest' or 'grpc')\n    // If gRPC extension is installed, 'grpc' can yield up to 5x-10x performance gains\n    'transport' => env('GENLYTICS_TRANSPORT', 'rest'),\n\n    // Cache Configuration\n    'cache' => [\n        'enabled' => env('GENLYTICS_ENABLE_CACHE', true),\n\n        // Dedicated store ('redis', 'memcached', or null for default)\n        'store' => env('GENLYTICS_CACHE_STORE', null),\n\n        // Cache key prefix to prevent collision\n        'prefix' => env('GENLYTICS_CACHE_PREFIX', 'genlytics'),\n\n        // Cache tags (supported on Redis and Memcached)\n        'use_tags' => env('GENLYTICS_CACHE_USE_TAGS', true),\n\n        // Global default cache TTL in seconds (fallback for all query types)\n        'default_ttl' => env('GENLYTICS_CACHE_TTL', 86400), // 24 hours\n\n        // Granular TTLs in seconds per query type\n        'ttl' => [\n            'report'    => env('GENLYTICS_CACHE_TTL_REPORT', 86400),    // 24 hours\n            'realtime'  => env('GENLYTICS_CACHE_TTL_REALTIME', 60),     // 1 minute\n            'dimension' => env('GENLYTICS_CACHE_TTL_DIMENSION', 86400), // 24 hours\n            'batch'     => env('GENLYTICS_CACHE_TTL_BATCH', 86400),     // 24 hours\n            'funnel'    => env('GENLYTICS_CACHE_TTL_FUNNEL', 43200),    // 12 hours\n            'ecommerce' => env('GENLYTICS_CACHE_TTL_ECOMMERCE', 43200), // 12 hours\n        ],\n\n        // Distributed lock duration to prevent queue stampedes\n        'lock_duration' => env('GENLYTICS_CACHE_LOCK_DURATION', 60),\n    ],\n\n    // Queue & Background Refresh\n    'queue' => [\n        'enabled' => env('GENLYTICS_USE_BACKGROUND_JOBS', false),\n        'connection' => env('GENLYTICS_QUEUE_CONNECTION', null),\n        'name' => env('GENLYTICS_QUEUE_NAME', 'analytics'),\n        'unique_for' => env('GENLYTICS_JOB_UNIQUE_FOR', 120),\n    ],\n];\n```\n\n---\n\n## Flexible Cache Expiry (TTL) Configuration\n\nGenlytics gives you 4 layers of cache expiry configuration, from environment variables down to individual queries.\n\n### 1. Environment & Config Defaults\nConfigure system-wide or per-type expiry directly in your `.env` file:\n\n```env\n# Global fallback TTL (e.g. 12 hours = 43200 seconds)\nGENLYTICS_CACHE_TTL=43200\n\n# Granular TTLs per operation type\nGENLYTICS_CACHE_TTL_REPORT=86400\nGENLYTICS_CACHE_TTL_REALTIME=30\nGENLYTICS_CACHE_TTL_DIMENSION=86400\nGENLYTICS_CACHE_TTL_BATCH=86400\nGENLYTICS_CACHE_TTL_FUNNEL=43200\nGENLYTICS_CACHE_TTL_ECOMMERCE=43200\n```\n\n### 2. Runtime Configuration via Facade & Service\nModify cache lifetimes dynamically during the application lifecycle or in scheduled commands:\n\n```php\nuse zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;\n\n// Update TTL for a specific query category (seconds)\nGenlytics::setCacheTtl(1800, 'report');\nGenlytics::setCacheTtl(30, 'realtime');\n\n// Inspect current TTL\n$reportTtl = Genlytics::getCacheTtl('report'); // 1800\n\n// Direct CacheManager access\nGenlytics::cache()->setDefaultLifetime(3600);\nGenlytics::cache()->setLifetime(7200, 'funnel');\n$lifetimes = Genlytics::cache()->getLifetimes();\n```\n\n### 3. Query-Level Cache Expiry (`remember`, `cacheFor`, `ttl`)\nOverride the cache duration for a specific query using Eloquent-style fluent methods. Supports integer seconds, `\\DateInterval`, or `\\DateTimeInterface`:\n\n```php\n// Integer seconds (2 hours)\n$data = Genlytics::query()\n    ->last7Days()\n    ->dimensions(['country', 'city'])\n    ->metrics(['activeUsers', 'sessions'])\n    ->remember(7200)\n    ->get();\n\n// DateInterval (e.g., 30 minutes)\n$data = Genlytics::query()\n    ->today()\n    ->dimension('pagePath')\n    ->metric('screenPageViews')\n    ->cacheFor(new \\DateInterval('PT30M'))\n    ->get();\n\n// DateTimeInterface (until midnight tonight)\n$data = Genlytics::query()\n    ->dimension('deviceCategory')\n    ->metric('activeUsers')\n    ->ttl(now()->endOfDay())\n    ->get();\n\n// Bypass cache entirely (force fresh query)\n$fresh = Genlytics::query()\n    ->last7Days()\n    ->dimension('country')\n    ->metric('activeUsers')\n    ->dontCache() // or ->noCache()\n    ->get();\n```\n\n### 4. Dedicated Helper Custom TTL\nAll dedicated helpers accept an optional `$ttl` parameter:\n\n```php\n// Cache top products for 30 minutes (1800s)\n$products = Genlytics::topProducts(10, '30daysAgo', 1800);\n\n// Cache categories for 1 hour\n$categories = Genlytics::topCategories(10, '30daysAgo', 3600);\n\n// Cache e-commerce purchase funnel for 6 hours\n$funnel = Genlytics::ecommerceFunnel('30daysAgo', 21600);\n```\n\n---\n\n## High-Scalability Caching: Stale-While-Revalidate\n\nUnder high traffic (e.g., hundreds of concurrent users loading an analytics dashboard), traditional caching often suffers from the **Thundering Herd** or **Cache Stampede** problem:\n1. When cache expires, 100 requests hit the API simultaneously.\n2. Google Analytics 4 API rate limits trigger (`429 ResourceExhausted`).\n\nGenlytics solves this using **Stale-While-Revalidate (SWR)** with **Atomic Distributed Locks**:\n\n```\nRequest arrives ────────► Cache Hit? \n                             │\n            ┌────────────────┴────────────────┐\n            ▼ Yes                             ▼ No\n    Return cached data           Synchronously fetch from GA4\n            │                    Populate cache & return\n    [Is Background Job Enabled?]\n            │\n            ▼\n    Acquire Atomic Lock (60s)?\n            │\n      ┌─────┴──────┐\n      ▼ Yes        ▼ No (Lock already held by another worker)\n Dispatch 1 Job    Do nothing (Prevent queue bombardment)\n to Queue\n```\n\n---\n\n## Cache Management Artisan Command\n\nGenlytics comes with an Artisan command to clear or inspect your analytics cache:\n\n```bash\n# Clear all Genlytics cache instantly (uses Redis tags if available):\nphp artisan genlytics:refresh-cache --clear\n\n# Refresh specific query policies:\nphp artisan genlytics:refresh-cache --type=report\nphp artisan genlytics:refresh-cache --type=realtime\n```\n",
  "api-reference": "# API Reference\n\nGenlytics provides two parallel access styles:\n1. **Raw Data Methods (`getReport`, `getRealTime`, etc.)**: Returns pure PHP `array` datasets. Recommended for internal services, queued jobs, console commands, and data pipelines.\n2. **HTTP Methods (`runReports`, `runRealTime`, etc.)**: Returns `Illuminate\\Http\\JsonResponse` objects. Ideal for direct controller returns.\n\n---\n\n## 1. Standard Reports\n\n### `Genlytics::getReport(...)` / `Genlytics::runReports(...)`\n\nQueries GA4 for historical analytics with dimensions, metrics, date ranges, and advanced filters.\n\n#### Signature:\n```php\npublic static function getReport(\n    array $period,\n    array|string $dimension,\n    array|string $metrics,\n    array $options = [],\n    bool $forceRefresh = false\n): array\n```\n\n#### Example:\n```php\nuse zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;\n\n$report = Genlytics::getReport(\n    period: ['start_date' => '30daysAgo', 'end_date' => 'today'],\n    dimension: ['country', 'city'],\n    metrics: ['activeUsers', 'sessions', 'screenPageViews'],\n    options: [\n        'limit' => 10,\n    ]\n);\n```\n\n#### Return Structure:\n```json\n[\n  {\n    \"dimensions\": {\n      \"country\": \"United States\",\n      \"city\": \"New York\"\n    },\n    \"metrics\": {\n      \"activeUsers\": \"4520\",\n      \"sessions\": \"6100\",\n      \"screenPageViews\": \"18400\"\n    }\n  }\n]\n```\n\n---\n\n## 2. Real-Time Reports\n\n### `Genlytics::getRealTime(...)` / `Genlytics::runRealTime(...)`\n\nFetches active user metrics from the last 30 minutes in real time.\n\n#### Signature:\n```php\npublic static function getRealTime(\n    array|string $dimension,\n    array|string $metrics,\n    array $options = [],\n    bool $forceRefresh = false\n): array\n```\n\n#### Example:\n```php\n$realtime = Genlytics::getRealTime(\n    dimension: 'country',\n    metrics: 'activeUsers',\n    options: ['limit' => 5]\n);\n```\n\n---\n\n## 3. Dimension-Only Reports\n\n### `Genlytics::getDimensionReport(...)` / `Genlytics::runDimensionReport(...)`\n\nRuns a lightweight query focusing solely on dimension values over a date range.\n\n#### Signature:\n```php\npublic static function getDimensionReport(\n    array $period,\n    string|array $dimension,\n    array $options = [],\n    bool $forceRefresh = false\n): array\n```\n\n#### Example:\n```php\n$dimensions = Genlytics::getDimensionReport(\n    period: ['start_date' => '7daysAgo', 'end_date' => 'today'],\n    dimension: 'pagePath',\n    options: ['limit' => 20]\n);\n```\n\n---\n\n## 4. Batch Reports (Multi-Report API)\n\n### `Genlytics::getBatchReports(...)` / `Genlytics::batchRunReports(...)`\n\nExecutes multiple distinct reports in **a single Google API HTTP request**. Essential for loading multi-widget dashboards.\n\n#### Signature:\n```php\npublic static function getBatchReports(\n    array $reports,\n    bool $forceRefresh = false\n): array\n```\n\n#### Example:\n```php\n$batch = Genlytics::getBatchReports([\n    'top_pages' => [\n        'dateRange'  => ['start_date' => '7daysAgo', 'end_date' => 'today'],\n        'dimensions' => ['pagePath'],\n        'metrics'    => ['screenPageViews'],\n        'options'    => ['limit' => 5],\n    ],\n    'top_countries' => [\n        'dateRange'  => ['start_date' => '7daysAgo', 'end_date' => 'today'],\n        'dimensions' => ['country'],\n        'metrics'    => ['activeUsers'],\n        'options'    => ['limit' => 5],\n    ],\n]);\n\n// $batch['top_pages'] => array of top pages\n// $batch['top_countries'] => array of top countries\n```\n\n---\n\n## 5. Cache Lifecycle & Runtime TTL\n\n### `Genlytics::setCacheTtl(...)`\nConfigures cache lifetime dynamically in seconds for a specific query type (`report`, `realtime`, `dimension`, `batch`, `funnel`, `ecommerce`) or globally if type is null.\n\n```php\nGenlytics::setCacheTtl(1800, 'report'); // 30 minutes for standard reports\nGenlytics::setCacheTtl(30, 'realtime');  // 30 seconds for realtime\n```\n\n### `Genlytics::getCacheTtl(...)`\nReturns current configured TTL in seconds.\n\n```php\n$ttl = Genlytics::getCacheTtl('report'); // 1800\n```\n\n### `Genlytics::cache()`\nReturns the underlying `CacheManagerInterface` instance.\n\n```php\n$cache = Genlytics::cache();\n$cache->setDefaultLifetime(3600);\n$cache->setLifetime(7200, 'funnel');\n$allTtls = $cache->getLifetimes();\n```\n\n",
  "batch-reporting": "# High-Performance Batch Reporting\n\nWhen developing modern analytics dashboards, a single view commonly displays multiple data cards:\n- Top Active Countries\n- Traffic Acquisition Channels\n- Most Viewed URLs\n- Daily Trend of Active Users\n\n---\n\n## The Sequential Request Problem\n\nUnder a standard implementation, loading these 4 widgets triggers **4 separate network requests** to Google's servers:\n\n```\nWidget 1: HTTP Request 1 (300ms) ────────► \nWidget 2: HTTP Request 2 (300ms) ────────────────────► \nWidget 3: HTTP Request 3 (300ms) ────────────────────────────────► \nWidget 4: HTTP Request 4 (300ms) ────────────────────────────────────────────►\nTotal Latency: ~1,200ms | Quota Tokens Expended: 4 separate requests\n```\n\n---\n\n## The Batching Solution (`batchRunReports`)\n\nGoogle Analytics 4 Data API provides a dedicated batching interface. Genlytics exposes this natively via `Genlytics::getBatchReports()` and `Genlytics::batchRunReports()`:\n\n```\n[ Request 1 + Request 2 + Request 3 + Request 4 ] (1 HTTP Call: 350ms) ──────►\nTotal Latency: ~350ms (70%+ faster) | Quota Tokens Conserved\n```\n\n---\n\n## Code Example: Loading a Complete Dashboard\n\n```php\nuse zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;\n\nclass AnalyticsDashboardController extends Controller\n{\n    public function index()\n    {\n        $dashboardData = Genlytics::getBatchReports([\n            'traffic_sources' => [\n                'dateRange'  => ['start_date' => '30daysAgo', 'end_date' => 'today'],\n                'dimensions' => ['sessionSource'],\n                'metrics'    => ['sessions', 'activeUsers'],\n                'options'    => ['limit' => 10],\n            ],\n            'top_pages' => [\n                'dateRange'  => ['start_date' => '30daysAgo', 'end_date' => 'today'],\n                'dimensions' => ['pagePath'],\n                'metrics'    => ['screenPageViews'],\n                'options'    => ['limit' => 10],\n            ],\n            'geo_distribution' => [\n                'dateRange'  => ['start_date' => '30daysAgo', 'end_date' => 'today'],\n                'dimensions' => ['country'],\n                'metrics'    => ['activeUsers'],\n                'options'    => ['limit' => 10],\n            ],\n        ]);\n\n        return view('dashboard.analytics', [\n            'sources'   => $dashboardData['traffic_sources'] ?? [],\n            'pages'     => $dashboardData['top_pages'] ?? [],\n            'countries' => $dashboardData['geo_distribution'] ?? [],\n        ]);\n    }\n}\n```\n\n",
  "test-coverage": "# Test Suite & Code Coverage Report\n\nGenlytics is rigorously verified with an enterprise-grade automated test suite executed via PHPUnit 10 and PHP 8.3 with Xdebug code coverage telemetry. Every core algorithm, concurrency lock, API resource, fluent query builder scope, and caching strategy is validated under isolated unit and integration test harnesses.\n\n---\n\n## 1. Executive Summary\n\n| Metric | Measured Value | Standard | Health Status |\n| :--- | :--- | :--- | :--- |\n| **Total Test Suites** | **19 Suites** | 100% Autonomous | `VERIFIED` |\n| **Total Test Cases** | **160 Tests** | 0 Failures, 0 Errors | `100% PASS` |\n| **Total Assertions** | **484 Assertions** | Comprehensive assertions | `OPTIMAL` |\n| **Line Code Coverage** | **90.43% (1,692 / 1,871 lines)** | Industry leading | `EXCELLENT` |\n| **Method Code Coverage** | **80.51% (223 / 277 methods)** | High API surface coverage | `OPTIMAL` |\n| **Classes at 100%** | **31 Classes (70.45%)** | Critical paths verified | `100% COVERAGE` |\n\n---\n\n## 2. Component-by-Component Coverage Breakdown\n\nBelow is the verified coverage report generated directly from Xdebug code coverage processing:\n\n### Core Commands, Events & Jobs (100% Coverage)\n| Component / Class | Methods | Lines Covered | Coverage % |\n| :--- | :--- | :--- | :--- |\n| `zfhassaan\\genlytics\\Commands\\ConsumeDailyQuota` | 1 / 1 (100%) | 63 / 63 | **100.00%** |\n| `zfhassaan\\genlytics\\Commands\\RefreshAnalyticsCache` | 1 / 1 (100%) | 21 / 21 | **100.00%** |\n| `zfhassaan\\genlytics\\Events\\AnalyticsCacheUpdated` | 1 / 1 (100%) | 1 / 1 | **100.00%** |\n| `zfhassaan\\genlytics\\Events\\AnalyticsDataFetched` | 1 / 1 (100%) | 1 / 1 | **100.00%** |\n| `zfhassaan\\genlytics\\Events\\AnalyticsDataRequested` | 1 / 1 (100%) | 1 / 1 | **100.00%** |\n| `zfhassaan\\genlytics\\Events\\AnalyticsQueryFailed` | 1 / 1 (100%) | 1 / 1 | **100.00%** |\n| `zfhassaan\\genlytics\\Events\\QuotaUsageUpdated` | 4 / 4 (100%) | 25 / 25 | **100.00%** |\n| `zfhassaan\\genlytics\\Listeners\\UpdateRealTimeCache` | 2 / 2 (100%) | 5 / 5 | **100.00%** |\n| `zfhassaan\\genlytics\\Jobs\\FetchAnalyticsDataJob` | 7 / 7 (100%) | 56 / 56 | **100.00%** |\n| `zfhassaan\\genlytics\\Exceptions\\QuotaExceededException` | 5 / 5 (100%) | 17 / 17 | **100.00%** |\n| `zfhassaan\\genlytics\\Exceptions\\QuotaValidationException` | 4 / 4 (100%) | 14 / 14 | **100.00%** |\n| `zfhassaan\\genlytics\\facades\\AnalyticsFacade` | 1 / 1 (100%) | 1 / 1 | **100.00%** |\n\n---\n\n### Strongly-Typed Data Transfer Objects (DTOs)\n| DTO Class | Methods | Lines Covered | Coverage % |\n| :--- | :--- | :--- | :--- |\n| `zfhassaan\\genlytics\\DTOs\\AnalyticsRowDTO` | 8 / 8 (100%) | 16 / 16 | **100.00%** |\n| `zfhassaan\\genlytics\\DTOs\\CategoryPerformanceDTO` | 4 / 4 (100%) | 23 / 23 | **100.00%** |\n| `zfhassaan\\genlytics\\DTOs\\CountryReportDTO` | 4 / 4 (100%) | 14 / 14 | **100.00%** |\n| `zfhassaan\\genlytics\\DTOs\\FunnelPipelineDTO` | 3 / 3 (100%) | 9 / 9 | **100.00%** |\n| `zfhassaan\\genlytics\\DTOs\\FunnelStepDTO` | 3 / 3 (100%) | 12 / 12 | **100.00%** |\n| `zfhassaan\\genlytics\\DTOs\\PageReportDTO` | 4 / 4 (100%) | 18 / 18 | **100.00%** |\n| `zfhassaan\\genlytics\\DTOs\\ProductPerformanceDTO` | 4 / 4 (100%) | 31 / 31 | **100.00%** |\n| `zfhassaan\\genlytics\\DTOs\\TrafficSourceDTO` | 4 / 4 (100%) | 18 / 18 | **100.00%** |\n| `zfhassaan\\genlytics\\DTOs\\UserEngagementDTO` | 4 / 4 (100%) | 17 / 17 | **100.00%** |\n| `zfhassaan\\genlytics\\DTOs\\QuotaStatusDTO` | 1 / 2 (50%) | 59 / 62 | **95.16%** |\n\n---\n\n### Standardized API Resources (`JsonResource` - 100% Coverage)\n| API Resource Class | Methods | Lines Covered | Coverage % |\n| :--- | :--- | :--- | :--- |\n| `Http\\Resources\\AnalyticsResource` | 1 / 1 (100%) | 15 / 15 | **100.00%** |\n| `Http\\Resources\\Concerns\\HasGenlyticsVersion` | 1 / 1 (100%) | 8 / 8 | **100.00%** |\n| `Http\\Resources\\AnalyticsResourceCollection` | 2 / 2 (100%) | 12 / 12 | **100.00%** |\n| `Http\\Resources\\CategoryPerformanceResource` | 1 / 1 (100%) | 4 / 4 | **100.00%** |\n| `Http\\Resources\\CountryReportResource` | 1 / 1 (100%) | 4 / 4 | **100.00%** |\n| `Http\\Resources\\FunnelPipelineResource` | 1 / 1 (100%) | 3 / 3 | **100.00%** |\n| `Http\\Resources\\PageReportResource` | 1 / 1 (100%) | 4 / 4 | **100.00%** |\n| `Http\\Resources\\ProductPerformanceResource` | 1 / 1 (100%) | 4 / 4 | **100.00%** |\n| `Http\\Resources\\QuotaStatusResource` | 1 / 1 (100%) | 38 / 38 | **100.00%** |\n| `Http\\Resources\\TrafficSourceResource` | 1 / 1 (100%) | 4 / 4 | **100.00%** |\n\n---\n\n### Core Querying, Services & Repositories\n| Class | Methods | Lines Covered | Coverage % |\n| :--- | :--- | :--- | :--- |\n| `zfhassaan\\genlytics\\Genlytics` | 46 / 55 (83.6%) | 296 / 309 | **95.79%** |\n| `zfhassaan\\genlytics\\Query\\AnalyticsQueryBuilder` | 40 / 45 (88.9%) | 130 / 138 | **94.20%** |\n| `zfhassaan\\genlytics\\Services\\CacheManager` | 14 / 17 (82.4%) | 62 / 67 | **92.54%** |\n| `zfhassaan\\genlytics\\Repositories\\AnalyticsRepository`| 5 / 11 (45.5%) | 115 / 125 | **92.00%** |\n| `zfhassaan\\genlytics\\Services\\DataTransformer` | 2 / 4 (50.0%) | 57 / 62 | **91.94%** |\n| `zfhassaan\\genlytics\\Services\\AnalyticsService` | 12 / 18 (66.7%) | 142 / 163 | **87.12%** |\n| `zfhassaan\\genlytics\\Services\\QuotaManager` | 21 / 31 (67.7%) | 289 / 339 | **85.25%** |\n| `provider\\AnalyticsServiceProvider` | 2 / 3 (66.7%) | 71 / 87 | **81.61%** |\n| `zfhassaan\\genlytics\\Http\\Controllers\\QuotaStreamController` | 2 / 3 (66.7%) | 11 / 24 | **45.83%** |\n\n---\n\n## 3. How to Run the Test Suite & Coverage Reports\n\n### Run All 160 Unit & Integration Tests\n```bash\n# Using Composer shortcut:\ncomposer test:genlytics\n\n# Or directly with PHP:\nphp packages/zfhassaan/genlytics/test-runner.php\n```\n\n### Run with TestDox Verbose Output\n```bash\nphp packages/zfhassaan/genlytics/test-runner.php --testdox\n```\n\n### Run Terminal Text Code Coverage (90.4%)\n```bash\ncomposer test:genlytics:coverage\n# or:\nphp -d xdebug.mode=coverage packages/zfhassaan/genlytics/test-runner.php --coverage-text\n```\n\n### Generate Interactive HTML Coverage Report\n```bash\ncomposer test:genlytics:coverage-html\n# or:\nphp -d xdebug.mode=coverage packages/zfhassaan/genlytics/test-runner.php --coverage-html coverage\n```\nThe interactive HTML coverage report is generated directly into the `coverage/` directory:\n- Open `coverage/index.html` in your browser to inspect line-by-line green/red code coverage for any class.\n",
  "faq": "# Frequently Asked Questions & Troubleshooting\n\n---\n\n### 1. `Google\\ApiCore\\ApiException: 403 Forbidden / Permission denied`\n**Cause**: The Google Cloud Service Account has not been added to your Google Analytics 4 property, or has insufficient permissions.\n\n**Solution**:\n1. Open [Google Analytics](https://analytics.google.com/).\n2. Navigate to **Admin** > **Property Access Management**.\n3. Click **Add User** and paste your service account client email (`xxxx@xxxx.iam.gserviceaccount.com`).\n4. Ensure the assigned role is at least **Viewer** or **Analyst**.\n\n---\n\n### 2. Can I pass credentials as an array instead of a file path?\n**Yes!** If you store your credentials in an environment variable or secrets manager (e.g., AWS Secrets Manager, HashiCorp Vault), you can pass an array directly:\n\n```php\n// In config/analytics.php:\n'service_account_credentials_json' => json_decode(env('GOOGLE_SERVICE_ACCOUNT_JSON'), true),\n```\n\n---\n\n### 3. `429 Too Many Requests / ResourceExhausted`\n**Cause**: Your application is making more concurrent requests or consuming more tokens than allowed by your GA4 property quota.\n\n**Solution**:\n1. Turn on caching in `.env`: `GENLYTICS_ENABLE_CACHE=true`.\n2. Use Redis for atomic locking: `GENLYTICS_CACHE_STORE=redis`.\n3. Switch sequential queries to `Genlytics::getBatchReports(...)`.\n4. Ensure `GENLYTICS_USE_BACKGROUND_JOBS=true` so queries refresh via queues with built-in deduplication (`ShouldBeUnique`).\n\n---\n\n### 4. How do I test code using Genlytics without calling Google API?\nYou can easily mock `AnalyticsRepositoryInterface` or use Laravel's facade fake:\n\n```php\nuse zfhassaan\\genlytics\\Contracts\\AnalyticsRepositoryInterface;\nuse Google\\Analytics\\Data\\V1beta\\RunReportResponse;\n\npublic function test_my_analytics_feature()\n{\n    $mockRepo = $this->createMock(AnalyticsRepositoryInterface::class);\n    $mockRepo->method('runReport')->willReturn(new RunReportResponse());\n    \n    $this->app->instance(AnalyticsRepositoryInterface::class, $mockRepo);\n    \n    // Run your application code\n}\n```\n\n---\n\n### 5. Does Genlytics work with Lumen?\nYes, Genlytics can be used in Lumen by manually registering the service provider in `bootstrap/app.php`:\n\n```php\n$app->register(zfhassaan\\genlytics\\provider\\AnalyticsServiceProvider::class);\n```\n\n"
};

const app = createApp({
  setup() {
    const currentPage = ref('home');
    const lastDocPage = ref('getting-started');
    const isDarkMode = ref(false);
    const docsSearch = ref('');
    const isSearchModalOpen = ref(false);
    const selectedSearchIndex = ref(0);
    const modalSearchInputRef = ref(null);
    const copiedText = ref('');
    const studioTab = ref('fluent');
    const selectedHelper = ref('topPages');
    const batchPreset = ref('full_dashboard');

    const docNavSections = [
      {
        group: 'Getting Started',
        items: [
          { id: 'getting-started', title: 'Installation & GCP Setup', icon: 'play-circle' },
          { id: 'configuration', title: 'Configuration & Caching', icon: 'settings' }
        ]
      },
      {
        group: 'Developer Experience',
        items: [
          { id: 'fluent-queries', title: 'Fluent Query Builder & Helpers', icon: 'terminal' },
          { id: 'ecommerce-and-funnels', title: 'E-Commerce & Conversion Funnels', icon: 'git-merge' },
          { id: 'dtos-and-resources', title: 'Response DTOs & API Resources', icon: 'box' },
          { id: 'batch-reporting', title: 'Batch Reporting (Multi-Widget)', icon: 'cpu' }
        ]
      },
      {
        group: 'Architecture',
        items: [
          { id: 'architecture', title: 'SOLID & Repository Pattern', icon: 'layers' },
          { id: 'quota-and-limits', title: 'GA4 Quota & Limits (200k/day)', icon: 'gauge' },
          { id: 'test-coverage', title: 'Test Coverage & Verification (90.4%)', icon: 'shield-check' }
        ]
      },
      {
        group: 'Reference',
        items: [
          { id: 'api-reference', title: 'Complete API Reference', icon: 'code' },
          { id: 'faq', title: 'Troubleshooting & FAQ', icon: 'help-circle' }
        ]
      }
    ];

    const docsCatalog = [];
    docNavSections.forEach(sec => {
      sec.items.forEach(item => {
        docsCatalog.push({ ...item, group: sec.group });
      });
    });

    const escapeRegex = (str) => {
      return str.replace(/[.*+?^$\{}()|[\]\\]/g, '\\$&');
    };

    const highlightText = (text, query) => {
      if (!text || !query) return text || '';
      try {
        const escaped = escapeRegex(query);
        return text.replace(new RegExp('(' + escaped + ')', 'gi'), '<mark class="search-highlight">$1</mark>');
      } catch (e) {
        return text;
      }
    };

    const searchResults = computed(() => {
      const q = (docsSearch.value || '').trim();
      if (!q) return [];
      const qLower = q.toLowerCase();
      const results = [];

      docsCatalog.forEach(doc => {
        const rawContent = docsContent[doc.id] || '';
        const titleMatch = doc.title.toLowerCase().includes(qLower);
        const groupMatch = doc.group.toLowerCase().includes(qLower);

        let matchCount = titleMatch ? 2 : 0;
        try {
          const contentMatches = rawContent.match(new RegExp(escapeRegex(q), 'gi'));
          if (contentMatches) matchCount += contentMatches.length;
        } catch (e) {}

        if (matchCount === 0 && !groupMatch) {
          return;
        }

        let bestHeading = '';
        let bestSnippet = '';
        const lines = rawContent.split('\n');
        let currentHeading = '';

        for (let i = 0; i < lines.length; i++) {
          const rawLine = lines[i];
          const trimmed = rawLine.trim();

          if (trimmed.startsWith('#')) {
            currentHeading = trimmed.replace(/^#+\s*/, '').trim();
            if (currentHeading.toLowerCase().includes(qLower) && !bestHeading) {
              bestHeading = currentHeading;
            }
          } else if (trimmed.toLowerCase().includes(qLower) && !bestSnippet) {
            const clean = trimmed
              .split(String.fromCharCode(96)).join('')
              .replace(/\*\*([^*]+)\*\*/g, '$1')
              .replace(/\*([^*]+)\*/g, '$1')
              .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1')
              .replace(/^[>\-\*[0-9]\.\s|:]+/, '')
              .trim();

            if (clean.length > 5) {
              const idx = clean.toLowerCase().indexOf(qLower);
              const start = Math.max(0, idx - 45);
              const end = Math.min(clean.length, idx + q.length + 65);
              let snip = clean.slice(start, end).replace(/\s+/g, ' ').trim();
              if (start > 0) snip = '...' + snip;
              if (end < clean.length) snip = snip + '...';
              bestSnippet = snip;
              if (!bestHeading && currentHeading) {
                bestHeading = currentHeading;
              }
            }
          }
        }

        let score = 0;
        if (titleMatch) score += 100;
        if (bestHeading && bestHeading.toLowerCase().includes(qLower)) score += 50;
        score += Math.min(matchCount * 2, 50);

        results.push({
          id: doc.id,
          title: doc.title,
          group: doc.group,
          icon: doc.icon,
          heading: bestHeading,
          snippet: bestSnippet,
          matchCount: Math.max(1, matchCount),
          score: score,
          highlightedTitle: highlightText(doc.title, q),
          highlightedHeading: highlightText(bestHeading, q),
          highlightedSnippet: highlightText(bestSnippet, q)
        });
      });

      return results.sort((a, b) => b.score - a.score);
    });

    const popularSearchTags = [
      'quota',
      'cache',
      'fluent',
      'resources',
      'funnel',
      'batch',
      'test-coverage',
      'events'
    ];

    const quickJumpDocs = [
      { id: 'getting-started', title: 'Installation & Setup', desc: 'Composer require, config publishing, service account credentials', group: 'Getting Started', icon: 'play-circle' },
      { id: 'fluent-queries', title: 'Fluent Query Builder', desc: 'Query GA4 dimensions & metrics with Eloquent syntax', group: 'Developer Experience', icon: 'terminal' },
      { id: 'quota-and-limits', title: 'GA4 Quota & Limits', desc: '200k daily tokens, PST midnight auto-reset, burst pacing', group: 'Architecture', icon: 'gauge' },
      { id: 'ecommerce-and-funnels', title: 'E-Commerce Funnels', desc: 'Multi-step dropoff analysis, top products and categories', group: 'Developer Experience', icon: 'git-merge' },
      { id: 'dtos-and-resources', title: 'DTOs & API Resources', desc: 'Strictly-typed responses, automatic package versioning', group: 'Developer Experience', icon: 'box' },
      { id: 'test-coverage', title: 'Test Coverage & Verification', desc: '160 automated tests, 484 assertions, 90.43% code coverage', group: 'Architecture', icon: 'shield-check' }
    ];

    const openSearchModal = (initialQuery = '') => {
      isSearchModalOpen.value = true;
      if (typeof initialQuery === 'string' && initialQuery.trim()) {
        docsSearch.value = initialQuery;
      }
      selectedSearchIndex.value = 0;
      nextTick(() => {
        if (modalSearchInputRef.value) {
          modalSearchInputRef.value.focus();
          modalSearchInputRef.value.select();
        }
        refreshLucideIcons();
      });
    };

    const closeSearchModal = () => {
      isSearchModalOpen.value = false;
      refreshLucideIcons();
    };

    const setSearchQuery = (tag) => {
      docsSearch.value = tag;
      selectedSearchIndex.value = 0;
      nextTick(() => {
        if (modalSearchInputRef.value) {
          modalSearchInputRef.value.focus();
        }
        refreshLucideIcons();
      });
    };

    const navigateSearchResults = (delta) => {
      if (!searchResults.value || searchResults.value.length === 0) return;
      const count = searchResults.value.length;
      selectedSearchIndex.value = (selectedSearchIndex.value + delta + count) % count;
      nextTick(() => {
        const activeItem = document.querySelector('.search-modal-result-active');
        if (activeItem && typeof activeItem.scrollIntoView === 'function') {
          activeItem.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
      });
    };

    const selectActiveSearchResult = () => {
      if (!searchResults.value || searchResults.value.length === 0) return;
      const target = searchResults.value[selectedSearchIndex.value] || searchResults.value[0];
      if (target) {
        selectSearchResult(target);
      }
    };

    const selectSearchResult = (result) => {
      navigateTo(result.id);
      closeSearchModal();
      nextTick(() => {
        refreshLucideIcons();
      });
    };

    const navigateToDoc = (docId) => {
      navigateTo(docId);
      closeSearchModal();
      nextTick(() => {
        refreshLucideIcons();
      });
    };

    const onSearchEnter = () => {
      if (searchResults.value && searchResults.value.length > 0) {
        selectSearchResult(searchResults.value[0]);
      }
    };

    watch(docsSearch, () => {
      selectedSearchIndex.value = 0;
      nextTick(() => {
        refreshLucideIcons();
      });
    });

    watch(isSearchModalOpen, (isOpen) => {
      try {
        if (isOpen) {
          document.body.style.overflow = 'hidden';
        } else if (!isMobileMenuOpen.value && !isMobileDocsDrawerOpen.value) {
          document.body.style.overflow = '';
        }
      } catch (e) {}
      nextTick(() => {
        refreshLucideIcons();
      });
    });

    // Mobile & Tablet Responsive Navigation State
    const isMobileMenuOpen = ref(false);
    const isMobileDocsDrawerOpen = ref(false);

    const toggleMobileMenu = () => {
      isMobileMenuOpen.value = !isMobileMenuOpen.value;
      if (isMobileMenuOpen.value) {
        isMobileDocsDrawerOpen.value = false;
        try { document.body.style.overflow = 'hidden'; } catch (e) {}
      } else {
        try { document.body.style.overflow = ''; } catch (e) {}
      }
      refreshLucideIcons();
    };

    const closeMobileMenu = () => {
      isMobileMenuOpen.value = false;
      if (!isSearchModalOpen.value && !isMobileDocsDrawerOpen.value) {
        try { document.body.style.overflow = ''; } catch (e) {}
      }
      refreshLucideIcons();
    };

    const toggleMobileDocsDrawer = () => {
      isMobileDocsDrawerOpen.value = !isMobileDocsDrawerOpen.value;
      if (isMobileDocsDrawerOpen.value) {
        isMobileMenuOpen.value = false;
        try { document.body.style.overflow = 'hidden'; } catch (e) {}
      } else {
        try { document.body.style.overflow = ''; } catch (e) {}
      }
      refreshLucideIcons();
    };

    const closeMobileDocsDrawer = () => {
      isMobileDocsDrawerOpen.value = false;
      if (!isSearchModalOpen.value && !isMobileMenuOpen.value) {
        try { document.body.style.overflow = ''; } catch (e) {}
      }
      refreshLucideIcons();
    };

    const getCurrentDocTitle = computed(() => {
      if (!isDocPage(currentPage.value)) return '';
      for (let i = 0; i < docNavSections.length; i++) {
        const found = docNavSections[i].items.find(item => item.id === currentPage.value);
        if (found) return found.title;
      }
      return 'Documentation';
    });

    const getCurrentDocGroup = computed(() => {
      if (!isDocPage(currentPage.value)) return '';
      for (let i = 0; i < docNavSections.length; i++) {
        const found = docNavSections[i].items.find(item => item.id === currentPage.value);
        if (found) return docNavSections[i].group;
      }
      return 'Guide';
    });

    // Live Real-Time Simulator Data
    const liveStats = ref({
      activeUsers: 148,
      sessions: 14280,
      views: 52140
    });

    // GA4 Quota & Limits Engine State
    const quotaPropertyType = ref('standard');
    const quotaMode = ref('maximum_utilization');
    const quotaDailyConsumed = ref(18420);
    const quotaHourlyConsumed = ref(3250);
    const quotaConcurrentActive = ref(2);
    const quotaProjectRequests = ref(842);
    const quotaPstCountdown = ref('12h 45m 10s');
    const quotaPacingStatus = ref('optimal');

    const quotaDailyLimit = computed(() => quotaPropertyType.value === '360' ? 2000000 : 200000);
    const quotaDailyRemaining = computed(() => Math.max(0, quotaDailyLimit.value - quotaDailyConsumed.value));
    const quotaDailyPercent = computed(() => ((quotaDailyConsumed.value / quotaDailyLimit.value) * 100).toFixed(1));
    const quotaHourlyLimit = 40000;
    const quotaHourlyRemaining = computed(() => Math.max(0, quotaHourlyLimit - quotaHourlyConsumed.value));
    const quotaHourlyPercent = computed(() => ((quotaHourlyConsumed.value / quotaHourlyLimit) * 100).toFixed(1));
    const quotaWarningThreshold = computed(() => {
      if (quotaMode.value === 'consume_all') return 100;
      return quotaMode.value === 'maximum_utilization' ? 99 : 85;
    });
    const quotaPacingBadge = computed(() => {
      if (quotaMode.value === 'consume_all') return '🔥 CONSUME ALL (100% DRAIN)';
      return quotaMode.value === 'maximum_utilization' ? 'MAX UTILIZATION (100% BURST)' : 'BALANCED (85% SAFE)';
    });
    const quotaUserLevelLimit = computed(() => {
      if (quotaMode.value === 'consume_all' || quotaMode.value === 'maximum_utilization') {
        return '1,000 req / 100s (Expanded)';
      }
      return '100 req / 100s (Default)';
    });

    const realTimeTelemetry = ref([
      { id: 1, event: 'QuotaUsageUpdated', tokens: 12, time: 'Just now' },
      { id: 2, event: 'QuotaUsageUpdated', tokens: 8, time: '3s ago' },
      { id: 3, event: 'QuotaUsageUpdated', tokens: 25, time: '7s ago' }
    ]);
    const drainInProgress = ref(false);

    function updatePstCountdown() {
      try {
        const now = new Date();
        const pstStr = now.toLocaleString('en-US', { timeZone: 'America/Los_Angeles' });
        const pstDate = new Date(pstStr);
        const pstMidnight = new Date(pstDate);
        pstMidnight.setHours(24, 0, 0, 0);
        const diffMs = Math.max(0, pstMidnight - pstDate);
        const hours = Math.floor(diffMs / 3600000);
        const mins = Math.floor((diffMs % 3600000) / 60000);
        const secs = Math.floor((diffMs % 60000) / 1000);
        quotaPstCountdown.value = `${hours}h ${String(mins).padStart(2, '0')}m ${String(secs).padStart(2, '0')}s`;
      } catch (e) {
        quotaPstCountdown.value = 'Midnight PST';
      }
    }

    function simulateQuotaExecution(isHeavy = false) {
      quotaConcurrentActive.value = Math.min(10, quotaConcurrentActive.value + (isHeavy ? 3 : 1));
      const tokens = isHeavy ? (Math.floor(Math.random() * 80) + 120) : (Math.floor(Math.random() * 15) + 5);
      quotaDailyConsumed.value += tokens;
      quotaHourlyConsumed.value += tokens;
      quotaProjectRequests.value += (isHeavy ? 3 : 1);
      
      realTimeTelemetry.value.unshift({
        id: Date.now(),
        event: 'QuotaUsageUpdated',
        tokens: tokens,
        time: 'Just now'
      });
      if (realTimeTelemetry.value.length > 4) realTimeTelemetry.value.pop();

      setTimeout(() => {
        quotaConcurrentActive.value = Math.max(1, quotaConcurrentActive.value - (isHeavy ? 3 : 1));
      }, 700);
    }

    function simulateDrainDailyQuota() {
      drainInProgress.value = true;
      quotaMode.value = 'consume_all';
      let count = 0;
      const interval = setInterval(() => {
        count++;
        const tokens = Math.floor(Math.random() * 850) + 400;
        quotaConcurrentActive.value = Math.min(10, quotaConcurrentActive.value + 2);
        quotaDailyConsumed.value = Math.min(quotaDailyLimit.value, quotaDailyConsumed.value + tokens);
        quotaHourlyConsumed.value += tokens;
        quotaProjectRequests.value += 4;
        
        realTimeTelemetry.value.unshift({
          id: Date.now(),
          event: 'QuotaUsageUpdated',
          tokens: tokens,
          time: 'Just now'
        });
        if (realTimeTelemetry.value.length > 4) realTimeTelemetry.value.pop();

        setTimeout(() => {
          quotaConcurrentActive.value = Math.max(2, quotaConcurrentActive.value - 2);
        }, 400);

        if (count >= 5) {
          clearInterval(interval);
          drainInProgress.value = false;
        }
      }, 450);
    }

    let quotaInterval = null;

    const livePages = ref([
      { path: '/dashboard/analytics', readers: 42 },
      { path: '/docs/fluent-queries', readers: 35 },
      { path: '/pricing', readers: 28 },
      { path: '/checkout/success', readers: 19 },
      { path: '/blog/laravel-ga4-guide', readers: 14 }
    ]);

    const liveCountries = ref([
      { country: 'United States', users: 64, pct: 85 },
      { country: 'United Kingdom', users: 31, pct: 50 },
      { country: 'Germany', users: 22, pct: 36 },
      { country: 'Canada', users: 16, pct: 28 },
      { country: 'Australia', users: 15, pct: 25 }
    ]);

    // Live E-Commerce Funnel Pipeline Data
    const liveFunnel = ref({
      name: 'E-Commerce Purchase Funnel',
      totalEntries: 14850,
      totalCompletions: 1240,
      overallConversionRate: 8.35,
      steps: [
        { step: 1, name: '1. View Item', eventName: 'view_item', count: 14850, dropOffCount: 0, dropOffRate: 0.0, conversionRate: 100.0, overallConversionRate: 100.0 },
        { step: 2, name: '2. Add to Cart', eventName: 'add_to_cart', count: 5720, dropOffCount: 9130, dropOffRate: 61.48, conversionRate: 38.52, overallConversionRate: 38.52 },
        { step: 3, name: '3. Begin Checkout', eventName: 'begin_checkout', count: 2410, dropOffCount: 3310, dropOffRate: 57.87, conversionRate: 42.13, overallConversionRate: 16.23 },
        { step: 4, name: '4. Purchase', eventName: 'purchase', count: 1240, dropOffCount: 1170, dropOffRate: 48.55, conversionRate: 51.45, overallConversionRate: 8.35 }
      ]
    });

    const topProductsList = ref([
      { id: 'SKU-ENT-01', name: 'Genlytics Enterprise Pro', category: 'Software / SaaS', revenue: 14250.00, itemsPurchased: 95, itemsViewed: 1820, cartToViewRate: 24.2 },
      { id: 'SKU-DEV-02', name: 'Developer Team Pass', category: 'Software / SaaS', revenue: 8890.00, itemsPurchased: 148, itemsViewed: 2450, cartToViewRate: 18.5 },
      { id: 'SKU-SLA-03', name: 'Priority 24/7 SLA Support', category: 'Support Services', revenue: 6400.00, itemsPurchased: 32, itemsViewed: 620, cartToViewRate: 12.1 },
      { id: 'SKU-CSL-04', name: 'Architecture & GA4 Audit', category: 'Consulting', revenue: 4950.00, itemsPurchased: 11, itemsViewed: 410, cartToViewRate: 9.8 }
    ]);

    const topCategoriesList = ref([
      { category: 'Software / SaaS', revenue: 23140.00, units: 243, pct: 100 },
      { category: 'Support Services', revenue: 6400.00, units: 32, pct: 28 },
      { category: 'Consulting', revenue: 4950.00, units: 11, pct: 21 },
      { category: 'Add-on Plugins', revenue: 2180.00, units: 84, pct: 10 }
    ]);

    // Live Simulation Tick
    let simInterval = null;
    const startSimulation = () => {
      simInterval = setInterval(() => {
        // Jitter active users naturally
        const delta = Math.floor(Math.random() * 5) - 2;
        liveStats.value.activeUsers = Math.max(120, liveStats.value.activeUsers + delta);
        liveStats.value.views += Math.floor(Math.random() * 4) + 1;
        liveStats.value.sessions += Math.random() > 0.6 ? 1 : 0;
      }, 2500);
    };

    // Available Dimensions and Metrics for Interactive Query Builder
    const availableDimensions = [
      'country', 'city', 'pagePath', 'pageTitle', 
      'sessionSource', 'sessionMedium', 'deviceCategory', 'date'
    ];

    const availableMetrics = [
      'activeUsers', 'sessions', 'screenPageViews', 'conversions', 
      'bounceRate', 'eventCount'
    ];

    // Fluent Builder State
    const fluentBuilder = ref({
      period: 'last7Days',
      dimensions: ['country', 'city'],
      metrics: ['activeUsers', 'sessions'],
      limit: 10,
      cacheTtl: 'default'
    });

    const helperOptions = [
      { name: 'ecommerceFunnel', desc: 'Default GA4 e-commerce purchase pipeline with drop-offs' },
      { name: 'topProducts', desc: 'Top performing products by revenue, views & purchases' },
      { name: 'topCategories', desc: 'Top product categories by revenue & volume' },
      { name: 'topPages', desc: 'Top pages by views and users' },
      { name: 'topCountries', desc: 'Geographic traffic by country' },
      { name: 'trafficSources', desc: 'Acquisition channels and mediums' },
      { name: 'userEngagement', desc: 'Engagement rate and duration' },
      { name: 'deviceCategories', desc: 'Mobile vs desktop breakdown' },
      { name: 'conversions', desc: 'Events and conversions by name' },
      { name: 'realtimeActiveUsers', desc: 'Active users in the last 30 minutes' }
    ];

    const dedicatedQueries = [
      {
        name: 'ecommerceFunnel',
        title: 'E-Commerce Funnel Pipeline',
        icon: 'git-merge',
        description: 'Sequential conversion pipeline (view_item -> add_to_cart -> checkout -> purchase) with drop-off rates.',
        code: 'Genlytics::ecommerceFunnel(period: "30daysAgo");'
      },
      {
        name: 'topProducts',
        title: 'Top Performing Products',
        icon: 'shopping-bag',
        description: 'Ranks products by gross item revenue, units purchased, views, and cart-to-view rate.',
        code: 'Genlytics::topProducts(limit: 10);'
      },
      {
        name: 'topCategories',
        title: 'Top Performing Categories',
        icon: 'layers',
        description: 'Analyzes revenue and volume breakdown across store categories with DTO hydration.',
        code: 'Genlytics::topCategories(limit: 5);'
      },
      {
        name: 'topPages',
        title: 'Top Viewed Pages',
        icon: 'file-text',
        description: 'Retrieves most visited URLs with screen views and average session duration.',
        code: 'Genlytics::topPages(limit: 10);'
      },
      {
        name: 'topCountries',
        title: 'Geographic Traffic',
        icon: 'globe',
        description: 'Ranks top visitor countries with active user counts and sessions.',
        code: 'Genlytics::topCountries(limit: 10);'
      },
      {
        name: 'trafficSources',
        title: 'Acquisition Channels',
        icon: 'trending-up',
        description: 'Analyzes sessions by sessionSource and sessionMedium with bounce rates.',
        code: 'Genlytics::trafficSources(limit: 10);'
      },
      {
        name: 'userEngagement',
        title: 'User Engagement',
        icon: 'clock',
        description: 'Overview of engaged sessions, engagement rate, and active users.',
        code: 'Genlytics::userEngagement();'
      },
      {
        name: 'deviceCategories',
        title: 'Device Breakdown',
        icon: 'smartphone',
        description: 'Mobile, desktop, and tablet distribution for responsive design insights.',
        code: 'Genlytics::deviceCategories();'
      },
      {
        name: 'realtimeActiveUsers',
        title: 'Live Real-Time Users',
        icon: 'radio',
        description: 'Single-call active user telemetry across the previous 30 minutes.',
        code: 'Genlytics::realtimeActiveUsers();'
      }
    ];

    const toggleFluentDim = (dim) => {
      const idx = fluentBuilder.value.dimensions.indexOf(dim);
      if (idx > -1) {
        fluentBuilder.value.dimensions.splice(idx, 1);
      } else {
        fluentBuilder.value.dimensions.push(dim);
      }
    };

    const toggleFluentMet = (met) => {
      const idx = fluentBuilder.value.metrics.indexOf(met);
      if (idx > -1) {
        fluentBuilder.value.metrics.splice(idx, 1);
      } else {
        fluentBuilder.value.metrics.push(met);
      }
    };

    const studioGeneratedCode = computed(() => {
      if (studioTab.value === 'fluent') {
        const dims = JSON.stringify(fluentBuilder.value.dimensions).replace(/"/g, "'");
        const mets = JSON.stringify(fluentBuilder.value.metrics).replace(/"/g, "'");
        const limit = fluentBuilder.value.limit;

        let cacheChain = '';
        if (fluentBuilder.value.cacheTtl === 'remember_7200') {
          cacheChain = '\n    ->remember(7200) // Cache for 2 hours';
        } else if (fluentBuilder.value.cacheTtl === 'cacheFor_interval') {
          cacheChain = '\n    ->cacheFor(new \\DateInterval(\'PT30M\')) // 30 mins TTL';
        } else if (fluentBuilder.value.cacheTtl === 'dontCache') {
          cacheChain = '\n    ->dontCache() // Bypass cache & fetch live data';
        }

        return `use zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;

// Eloquent-Style Fluent Chaining
\$analytics = Genlytics::query()
    ->${fluentBuilder.value.period}()
    ->dimensions(${dims})
    ->metrics(${mets})${cacheChain}
    ->orderBy('${fluentBuilder.value.metrics[0] || 'activeUsers'}', 'DESC')
    ->limit(${limit})
    ->get(); // Returns Illuminate\\Support\\Collection

// Use standard Collection methods
\$topRow = \$analytics->first();`;
      }

      if (studioTab.value === 'helpers') {
        if (selectedHelper.value === 'ecommerceFunnel') {
          return `use zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;

// E-Commerce Purchase Funnel with Drop-Off Analysis
\$funnel = Genlytics::ecommerceFunnel('30daysAgo');

echo "Funnel: " . \$funnel->name . " | Conversion: " . \$funnel->overallConversionRate . "%\n";

foreach (\$funnel->steps as \$step) {
    echo "Step {$step->step}: {$step->name} -> Count: {$step->count} (Drop-off: {$step->dropOffRate}%)\n";
}`;
        }

        if (selectedHelper.value === 'topProducts') {
          return `use zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;

// Top Performing Products Hydrated as Strongly-Typed DTOs
\$products = Genlytics::topProducts(limit: 10, period: '30daysAgo');

foreach (\$products as \$product) {
    echo "{\$product->name} [{\$product->category}]: \${\$product->revenue} ({\$product->itemsPurchased} sold)\n";
}`;
        }

        if (selectedHelper.value === 'topCategories') {
          return `use zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;

// Top Performing Product Categories by Revenue
\$categories = Genlytics::topCategories(limit: 5, period: '30daysAgo');

foreach (\$categories as \$category) {
    echo "{\$category->category}: \${\$category->revenue} ({\$category->cartToViewRate}% cart-to-view)\n";
}`;
        }

        if (selectedHelper.value === 'topPages') {
          return `use zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;

// Dedicated Helper: Top Viewed Pages
\$pages = Genlytics::topPages(limit: 10, period: '30daysAgo');

foreach (\$pages as \$page) {
    echo \$page['dimensions']['pagePath'] . ': ' . \$page['metrics']['screenPageViews'] . ' views';
}`;
        }

        if (selectedHelper.value === 'topCountries') {
          return `use zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;

// Dedicated Helper: Top Geographic Countries
\$countries = Genlytics::topCountries(limit: 10);

// Returns Collection: [['dimensions' => ['country' => 'United States'], 'metrics' => [...]]]`;
        }

        if (selectedHelper.value === 'realtimeActiveUsers') {
          return `use zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;

// Dedicated Helper: Active Users Right Now (last 30 minutes)
\$liveCount = Genlytics::realtimeActiveUsers(); // returns int (e.g. 148)`;
        }

        return `use zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;

// Dedicated Helper: ${selectedHelper.value}
\$data = Genlytics::${selectedHelper.value}();`;
      }

      // Batch Tab
      return `use zfhassaan\\genlytics\\facades\\AnalyticsFacade as Genlytics;

// Multi-Widget Batch Query in 1 Single HTTP Request (80%+ faster)
\$dashboard = Genlytics::getBatchReports([
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
]);`;
    });

    const isDocPage = (page) => {
      if (!page || typeof page !== 'string') return false;
      return Object.prototype.hasOwnProperty.call(docsContent, page) || [
        'getting-started',
        'fluent-queries',
        'ecommerce-and-funnels',
        'quota-and-limits',
        'dtos-and-resources',
        'architecture',
        'configuration',
        'api-reference',
        'batch-reporting',
        'faq'
      ].includes(page);
    };

    const refreshLucideIcons = () => {
      nextTick(() => {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
          window.lucide.createIcons();
        }
      });
    };

    const navigateTo = (page) => {
      if (!page) return;
      isMobileMenuOpen.value = false;
      isMobileDocsDrawerOpen.value = false;
      try { document.body.style.overflow = ''; } catch (e) {}
      currentPage.value = page;
      if (isDocPage(page)) {
        lastDocPage.value = page;
      }
      window.location.hash = page;
      try {
        localStorage.setItem('genlytics_current_page', page);
      } catch (e) {}
      window.scrollTo({ top: 0, behavior: 'smooth' });
      highlightCode();
      refreshLucideIcons();
    };

    const handleHashChange = () => {
      const hash = (window.location.hash || '').replace(/^#+/, '').trim();
      if (hash && (hash === 'home' || isDocPage(hash))) {
        if (currentPage.value !== hash) {
          currentPage.value = hash;
          if (isDocPage(hash)) {
            lastDocPage.value = hash;
          }
          try {
            localStorage.setItem('genlytics_current_page', hash);
          } catch (e) {}
          highlightCode();
          refreshLucideIcons();
        }
      }
    };

    const toggleTheme = () => {
      isDarkMode.value = !isDarkMode.value;
      const theme = isDarkMode.value ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', theme);
      if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
      } else {
        document.documentElement.classList.remove('dark');
      }
      localStorage.setItem('genlytics_theme', theme);
      refreshLucideIcons();
    };

    const switchStudioTab = (tab) => {
      studioTab.value = tab;
      refreshLucideIcons();
    };

    const copyText = (text) => {
      navigator.clipboard.writeText(text).then(() => {
        copiedText.value = text;
        refreshLucideIcons();
        setTimeout(() => {
          copiedText.value = '';
          refreshLucideIcons();
        }, 2000);
      });
    };

    const renderedMarkdown = computed(() => {
      if (!isDocPage(currentPage.value)) return '';
      const raw = docsContent[currentPage.value] || '# Page Not Found';
      nextTick(() => {
        highlightCode();
        refreshLucideIcons();
      });
      return marked.parse(raw);
    });

    const highlightCode = () => {
      nextTick(() => {
        if (window.Prism) {
          Prism.highlightAll();
        }
      });
    };

    onMounted(() => {
      // Restore theme
      const savedTheme = localStorage.getItem('genlytics_theme');
      if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        isDarkMode.value = true;
        document.documentElement.setAttribute('data-theme', 'dark');
        document.documentElement.classList.add('dark');
      } else {
        document.documentElement.setAttribute('data-theme', 'light');
        document.documentElement.classList.remove('dark');
      }

      // Check URL hash and sustain route across refreshes
      let targetRoute = 'home';
      const hash = (window.location.hash || '').replace(/^#+/, '').trim();
      if (hash && (hash === 'home' || isDocPage(hash))) {
        targetRoute = hash;
      } else {
        const saved = localStorage.getItem('genlytics_current_page');
        if (saved && (saved === 'home' || isDocPage(saved))) {
          targetRoute = saved;
        }
      }

      currentPage.value = targetRoute;
      if (isDocPage(targetRoute)) {
        lastDocPage.value = targetRoute;
      }
      window.location.hash = targetRoute;

      window.addEventListener('hashchange', handleHashChange);

      const handleGlobalKeydown = (e) => {
        if ((e.key === 'k' && (e.metaKey || e.ctrlKey)) || (e.key === '/' && !isSearchModalOpen.value && document.activeElement && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA')) {
          e.preventDefault();
          openSearchModal();
          return;
        }

        if (e.key === 'Escape') {
          if (isSearchModalOpen.value) {
            e.preventDefault();
            closeSearchModal();
            return;
          }
          if (isMobileMenuOpen.value) {
            e.preventDefault();
            closeMobileMenu();
            return;
          }
          if (isMobileDocsDrawerOpen.value) {
            e.preventDefault();
            closeMobileDocsDrawer();
            return;
          }
        }
      };

      window.addEventListener('keydown', handleGlobalKeydown);

      startSimulation();
      updatePstCountdown();
      quotaInterval = setInterval(updatePstCountdown, 1000);
      highlightCode();
      refreshLucideIcons();
    });

    onUnmounted(() => {
      window.removeEventListener('hashchange', handleHashChange);
      if (typeof handleGlobalKeydown !== 'undefined') {
        window.removeEventListener('keydown', handleGlobalKeydown);
      }
      if (simInterval) clearInterval(simInterval);
      if (quotaInterval) clearInterval(quotaInterval);
    });

    return {
      currentPage,
      lastDocPage,
      isDarkMode,
      docsSearch,
      isSearchModalOpen,
      selectedSearchIndex,
      modalSearchInputRef,
      popularSearchTags,
      quickJumpDocs,
      openSearchModal,
      closeSearchModal,
      setSearchQuery,
      navigateSearchResults,
      selectActiveSearchResult,
      navigateToDoc,
      docNavSections,
      searchResults,
      selectSearchResult,
      onSearchEnter,
      copiedText,
      studioTab,
      selectedHelper,
      batchPreset,
      liveStats,
      livePages,
      liveCountries,
      liveFunnel,
      topProductsList,
      topCategoriesList,
      availableDimensions,
      availableMetrics,
      fluentBuilder,
      helperOptions,
      dedicatedQueries,
      toggleFluentDim,
      toggleFluentMet,
      studioGeneratedCode,
      isDocPage,
      navigateTo,
      toggleTheme,
      switchStudioTab,
      refreshLucideIcons,
      copyText,
      renderedMarkdown,
      quotaPropertyType,
      quotaMode,
      quotaDailyConsumed,
      quotaHourlyConsumed,
      quotaConcurrentActive,
      quotaProjectRequests,
      quotaPstCountdown,
      quotaPacingStatus,
      quotaDailyLimit,
      quotaDailyRemaining,
      quotaDailyPercent,
      quotaHourlyLimit,
      quotaHourlyRemaining,
      quotaHourlyPercent,
      quotaWarningThreshold,
      quotaPacingBadge,
      quotaUserLevelLimit,
      realTimeTelemetry,
      drainInProgress,
      simulateQuotaExecution,
      simulateDrainDailyQuota,
      isMobileMenuOpen,
      isMobileDocsDrawerOpen,
      toggleMobileMenu,
      closeMobileMenu,
      toggleMobileDocsDrawer,
      closeMobileDocsDrawer,
      getCurrentDocTitle,
      getCurrentDocGroup
    };
  }
});

app.mount('#app');
