# E-Commerce & Conversion Funnel Pipelines

Genlytics provides first-class support for **E-Commerce Product Performance**, **Category Intelligence**, and **Step-by-Step Conversion Funnel Pipelines**. Easily analyze customer drop-off points, revenue drivers, and conversion velocity with zero boilerplate.

---

## 1. E-Commerce Purchase Funnel

The `Genlytics::ecommerceFunnel()` method analyzes the default Google Analytics 4 purchase journey:
1. `view_item` (Product Discovery)
2. `add_to_cart` (Cart Intent)
3. `begin_checkout` (Checkout Initiation)
4. `purchase` (Transaction Completion)

```php
use zfhassaan\genlytics\facades\AnalyticsFacade as Genlytics;

// Analyze funnel over the last 30 days
$funnel = Genlytics::ecommerceFunnel('30daysAgo');

echo "Funnel Name: {$funnel->name}\n";
echo "Total Entries: {$funnel->totalEntries}\n";
echo "Total Completions: {$funnel->totalCompletions}\n";
echo "Overall Conversion Rate: {$funnel->overallConversionRate}%\n";

// Iterate each sequential step in the pipeline
foreach ($funnel->steps as $step) {
    echo "Step {$step->step}: {$step->name} ({$step->eventName})\n";
    echo " - Count: {$step->count}\n";
    echo " - Drop-Off: {$step->dropOffCount} ({$step->dropOffRate}%)\n";
    echo " - Step Conversion: {$step->conversionRate}%\n";
    echo " - Overall Conversion: {$step->overallConversionRate}%\n";
}
```

### Response JSON Structure

```json
{
  "name": "E-Commerce Purchase Funnel",
  "total_entries": 14800,
  "total_completions": 1200,
  "overall_conversion_rate": 8.11,
  "steps": [
    {
      "step": 1,
      "name": "1. View Item",
      "event_name": "view_item",
      "count": 14800,
      "drop_off_count": 0,
      "drop_off_rate": 0.0,
      "conversion_rate": 100.0,
      "overall_conversion_rate": 100.0
    },
    {
      "step": 2,
      "name": "2. Add to Cart",
      "event_name": "add_to_cart",
      "count": 5700,
      "drop_off_count": 9100,
      "drop_off_rate": 61.49,
      "conversion_rate": 38.51,
      "overall_conversion_rate": 38.51
    },
    {
      "step": 3,
      "name": "3. Begin Checkout",
      "event_name": "begin_checkout",
      "count": 2400,
      "drop_off_count": 3300,
      "drop_off_rate": 57.89,
      "conversion_rate": 42.11,
      "overall_conversion_rate": 16.22
    },
    {
      "step": 4,
      "name": "4. Purchase",
      "event_name": "purchase",
      "count": 1200,
      "drop_off_count": 1200,
      "drop_off_rate": 50.0,
      "conversion_rate": 50.0,
      "overall_conversion_rate": 8.11
    }
  ]
}
```

---

## 2. Custom Conversion Funnels

You can define any arbitrary multi-step event sequence using `Genlytics::funnel()`:

```php
// Define custom SaaS onboarding pipeline
$saasFunnel = Genlytics::funnel([
    'Landing Page'   => 'page_view',
    'Sign Up Started' => 'sign_up_start',
    'Account Created' => 'account_created',
    'Subscribed'      => 'subscription_created',
], name: 'SaaS Onboarding Funnel', period: 'last30Days');
```

---

## 3. Top Performing Products

Query products ranked by gross revenue, units sold, view volume, and cart-to-view rate. Each row is hydrated as a strongly-typed `ProductPerformanceDTO`:

```php
use zfhassaan\genlytics\facades\AnalyticsFacade as Genlytics;

// Get top 10 products
$products = Genlytics::topProducts(limit: 10, period: '30daysAgo');

foreach ($products as $product) {
    echo "SKU: {$product->id}\n";
    echo "Name: {$product->name}\n";
    echo "Category: {$product->category}\n";
    echo "Revenue: \${$product->revenue}\n";
    echo "Purchased: {$product->itemsPurchased} units\n";
    echo "Cart-to-View Rate: {$product->cartToViewRate}%\n";
}
```

### ProductPerformanceDTO Properties

| Property | Type | Description |
|---|---|---|
| `id` | `string` | GA4 `itemId` (SKU or identifier) |
| `name` | `string` | GA4 `itemName` |
| `category` | `string` | GA4 `itemCategory` |
| `revenue` | `float` | Total monetary gross item revenue |
| `itemsPurchased` | `int` | Total units purchased |
| `itemsViewed` | `int` | Total product detail page impressions |
| `itemsAddedToCart` | `int` | Total units added to shopping bag |
| `cartToViewRate` | `float` | Percentage of views that generated cart additions |
| `purchaseToViewRate` | `float` | Percentage of views that completed checkout |

---

## 4. Top Performing Product Categories

Analyze revenue distribution across store categories using `Genlytics::topCategories()`:

```php
use zfhassaan\genlytics\facades\AnalyticsFacade as Genlytics;

// Get top 5 product categories
$categories = Genlytics::topCategories(limit: 5, period: '30daysAgo');

foreach ($categories as $cat) {
    echo "Category: {$cat->category}\n";
    echo "Revenue: \${$cat->revenue}\n";
    echo "Units Sold: {$cat->itemsPurchased}\n";
    echo "Cart-to-View Rate: {$cat->cartToViewRate}%\n";
}
```

---

## 5. Standardized Laravel API Resources

All e-commerce and funnel DTOs seamlessly integrate with Laravel API Resources for clean controller responses:

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use zfhassaan\genlytics\facades\AnalyticsFacade as Genlytics;
use zfhassaan\genlytics\Http\Resources\ProductPerformanceResource;
use zfhassaan\genlytics\Http\Resources\FunnelPipelineResource;

class AnalyticsReportController extends Controller
{
    public function funnel()
    {
        $funnel = Genlytics::ecommerceFunnel('last30Days');
        return new FunnelPipelineResource($funnel);
    }

    public function products()
    {
        $products = Genlytics::topProducts(limit: 15);
        return ProductPerformanceResource::collection($products);
    }
}
```

