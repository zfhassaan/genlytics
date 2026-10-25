# Frequently Asked Questions & Troubleshooting

---

### 1. `Google\ApiCore\ApiException: 403 Forbidden / Permission denied`
**Cause**: The Google Cloud Service Account has not been added to your Google Analytics 4 property, or has insufficient permissions.

**Solution**:
1. Open [Google Analytics](https://analytics.google.com/).
2. Navigate to **Admin** > **Property Access Management**.
3. Click **Add User** and paste your service account client email (`xxxx@xxxx.iam.gserviceaccount.com`).
4. Ensure the assigned role is at least **Viewer** or **Analyst**.

---

### 2. Can I pass credentials as an array instead of a file path?
**Yes!** If you store your credentials in an environment variable or secrets manager (e.g., AWS Secrets Manager, HashiCorp Vault), you can pass an array directly:

```php
// In config/analytics.php:
'service_account_credentials_json' => json_decode(env('GOOGLE_SERVICE_ACCOUNT_JSON'), true),
```

---

### 3. `429 Too Many Requests / ResourceExhausted`
**Cause**: Your application is making more concurrent requests or consuming more tokens than allowed by your GA4 property quota.

**Solution**:
1. Turn on caching in `.env`: `GENLYTICS_ENABLE_CACHE=true`.
2. Use Redis for atomic locking: `GENLYTICS_CACHE_STORE=redis`.
3. Switch sequential queries to `Genlytics::getBatchReports(...)`.
4. Ensure `GENLYTICS_USE_BACKGROUND_JOBS=true` so queries refresh via queues with built-in deduplication (`ShouldBeUnique`).

---

### 4. How do I test code using Genlytics without calling Google API?
You can easily mock `AnalyticsRepositoryInterface` or use Laravel's facade fake:

```php
use zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface;
use Google\Analytics\Data\V1beta\RunReportResponse;

public function test_my_analytics_feature()
{
    $mockRepo = $this->createMock(AnalyticsRepositoryInterface::class);
    $mockRepo->method('runReport')->willReturn(new RunReportResponse());
    
    $this->app->instance(AnalyticsRepositoryInterface::class, $mockRepo);
    
    // Run your application code
}
```

---

### 5. Does Genlytics work with Lumen?
Yes, Genlytics can be used in Lumen by manually registering the service provider in `bootstrap/app.php`:

```php
$app->register(zfhassaan\genlytics\provider\AnalyticsServiceProvider::class);
```

---

### 6. Which versions of Laravel and PHP are supported?
Genlytics fully supports **Laravel 10.x, 11.x, 12.x, and 13.x** on **PHP 8.0, 8.1, 8.2, 8.3, 8.4, 8.5, and 8.6**.
All core contracts and Illuminate dependencies are strictly decoupled, allowing seamless upgrades across major framework versions without dependency conflicts.


