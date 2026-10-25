# Architecture & SOLID Principles

Genlytics is architected strictly around **SOLID principles**, the **Repository pattern**, and an **Event-driven design**.

---

## High-Level Architecture Overview

```
[ Your Application / Controller / Console / Job ]
                       │
                       ▼
             [ Genlytics Facade ]
                       │
                       ▼
            [ AnalyticsService ] (Orchestrator & SWR Caching)
            ┌──────────┴──────────────┬──────────────────┐
            ▼                         ▼                  ▼
[ AnalyticsRepositoryInterface ] [ CacheManagerInterface ] [ DataTransformerInterface ]
            │                         │                  │
            ▼                         ▼                  ▼
[ AnalyticsRepository (GA4 API) ] [ CacheManager (Redis) ] [ DataTransformer (Array) ]
```

---

## SOLID Principles in Practice

### 1. Single Responsibility Principle (SRP)
Each class has exactly one clear reason to change:
- **`AnalyticsRepository`**: Focuses 100% on communication with the Google Analytics Data API, converting parameters into protobuf requests (`DateRange`, `Dimension`, `Metric`).
- **`CacheManager`**: Dedicated to cache storage, granular TTLs, tagging, key generation, and distributed atomic locks.
- **`DataTransformer`**: Transforms complex Google API Protobuf responses into clean, predictable PHP arrays or JSON-ready structures.
- **`AnalyticsService`**: Orchestrates data access, cache verification, lock checks, and event dispatches.
- **`Genlytics`**: Client-facing adapter providing seamless Facade access and backward compatibility.

### 2. Open/Closed Principle (OCP)
Classes are open for extension without modifying existing contracts. For example, the addition of GA4's `batchRunReports` seamlessly extends the repository and service contracts without touching single-report execution code paths.

### 3. Liskov Substitution Principle (LSP)
All high-level services interact strictly through interfaces:
- `AnalyticsRepositoryInterface`
- `CacheManagerInterface`
- `DataTransformerInterface`

Any custom implementation (for instance, an in-memory test repository or a custom database cache driver) can be substituted in Laravel's service container without breaking any callers.

### 4. Interface Segregation Principle (ISP)
Interfaces are small, cohesive, and client-specific. Callers only depend on the methods they actually consume.

### 5. Dependency Inversion Principle (DIP)
Dependencies are injected through abstractions rather than concretions. `AnalyticsServiceProvider` binds all implementations into the Laravel IoC container:

```php
$this->app->singleton(AnalyticsRepositoryInterface::class, function ($app) {
    ...
    return new AnalyticsRepository($client, $propertyId);
});
```

---

## Event-Driven Architecture

Genlytics emits lifecycle events throughout the query flow:

| Event | Fired When | Payload |
| :--- | :--- | :--- |
| `AnalyticsDataRequested` | A report query is initiated | `$type`, `$parameters`, `$forceRefresh` |
| `AnalyticsDataFetched` | Data is fetched (from cache or API) | `$type`, `$data`, `$parameters`, `$fromCache` |
| `AnalyticsCacheUpdated` | Data is successfully saved to cache | `$cacheKey`, `$type`, `$data`, `$parameters` |
| `AnalyticsQueryFailed` | An API or processing error occurs | `$type`, `$parameters`, `$exception` |

### Listening to Events

You can attach listeners to track analytics usage, alert on API quota errors, or stream real-time metrics over WebSockets:

```php
use zfhassaan\genlytics\Events\AnalyticsDataFetched;

Event::listen(AnalyticsDataFetched::class, function (AnalyticsDataFetched $event) {
    Log::info("Analytics query complete", [
        'type' => $event->type,
        'from_cache' => $event->fromCache,
    ]);
});
```

