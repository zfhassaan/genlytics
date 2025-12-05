# Genlytics Architecture

## Overview

Genlytics has been redesigned following SOLID principles, Repository pattern, and Event-driven architecture for optimal performance and maintainability.

## Architecture Principles

### SOLID Principles

1. **Single Responsibility Principle (SRP)**
   - Each class has one reason to change
   - `AnalyticsRepository`: Data access only
   - `CacheManager`: Caching operations only
   - `DataTransformer`: Data transformation only
   - `AnalyticsService`: Business logic orchestration

2. **Open/Closed Principle (OCP)**
   - Open for extension, closed for modification
   - Interfaces allow extending functionality without modifying existing code
   - New implementations can be added via dependency injection

3. **Liskov Substitution Principle (LSP)**
   - Implementations can be substituted without breaking functionality
   - All implementations follow their interface contracts

4. **Interface Segregation Principle (ISP)**
   - Clients depend only on interfaces they use
   - Focused interfaces: `AnalyticsRepositoryInterface`, `CacheManagerInterface`, `DataTransformerInterface`

5. **Dependency Inversion Principle (DIP)**
   - High-level modules depend on abstractions, not concretions
   - All dependencies are injected via interfaces

## Design Patterns

### Repository Pattern

The Repository pattern abstracts data access:

```
AnalyticsRepositoryInterface (Contract)
    ↓
AnalyticsRepository (Implementation)
    ↓
BetaAnalyticsDataClient (Google API Client)
```

**Benefits:**
- Testability: Easy to mock for unit tests
- Flexibility: Can swap implementations
- Separation of concerns: Business logic separated from data access

### Event-Driven Architecture

Events are fired at key points:

```
AnalyticsDataRequested → AnalyticsDataFetched → AnalyticsCacheUpdated
                    ↓
            AnalyticsQueryFailed (on error)
```

**Events:**
- `AnalyticsDataRequested`: When data is requested
- `AnalyticsDataFetched`: When data is successfully fetched
- `AnalyticsCacheUpdated`: When cache is updated
- `AnalyticsQueryFailed`: When a query fails

**Listeners:**
- `UpdateRealTimeCache`: Automatically refreshes real-time data

### Service Layer Pattern

Business logic is encapsulated in services:

```
Genlytics (Facade)
    ↓
AnalyticsService (Orchestration)
    ↓
AnalyticsRepository + CacheManager + DataTransformer
```

## Component Structure

### Contracts (Interfaces)

Located in `src/Contracts/`:

- `AnalyticsRepositoryInterface`: Data access contract
- `CacheManagerInterface`: Caching operations contract
- `DataTransformerInterface`: Data transformation contract

### Repositories

Located in `src/Repositories/`:

- `AnalyticsRepository`: Concrete implementation of data access

### Services

Located in `src/Services/`:

- `AnalyticsService`: Main business logic orchestration
- `CacheManager`: Cache operations implementation
- `DataTransformer`: Data transformation implementation
- `RealTimeUpdateService`: Real-time update management

### Events

Located in `src/Events/`:

- `AnalyticsDataRequested`: Fired when data is requested
- `AnalyticsDataFetched`: Fired when data is fetched
- `AnalyticsCacheUpdated`: Fired when cache is updated
- `AnalyticsQueryFailed`: Fired when query fails

### Jobs

Located in `src/Jobs/`:

- `FetchAnalyticsDataJob`: Background job for fetching analytics data

### Commands

Located in `src/Commands/`:

- `RefreshAnalyticsCache`: Artisan command for cache management

## Data Flow

### Standard Report Flow

```
1. Request → Genlytics::runReports()
2. AnalyticsService checks cache
3. If cached: Return cached data + dispatch background refresh
4. If not cached: Fetch from API → Transform → Cache → Return
5. Events fired at each step
```

### Real-Time Report Flow

```
1. Request → Genlytics::runRealTime()
2. Check short-lived cache (30 seconds)
3. Fetch fresh data if needed
4. Cache for 30 seconds
5. Schedule next update via listener
6. Events fired
```

### Background Job Flow

```
1. FetchAnalyticsDataJob dispatched
2. Fetch data from API
3. Transform data
4. Update cache
5. Fire AnalyticsCacheUpdated event
6. Listener schedules next update (for real-time)
```

## Caching Strategy

### Cache Keys

Generated using MD5 hash of parameters:
```
genlytics:report:abc123def456...
genlytics:realtime:xyz789...
genlytics:dimension:def456...
```

### Cache Lifetime

- **Standard Reports**: Configurable (default: 24 hours)
- **Real-Time Reports**: 30 seconds (configurable)

### Cache Refresh

- **Automatic**: Background jobs refresh cache
- **Manual**: `php artisan genlytics:refresh-cache`
- **Force**: Pass `$forceRefresh = true` to methods

## Performance Optimizations

1. **Caching**: Reduces API calls by 90%+
2. **Background Processing**: Non-blocking requests
3. **Query Optimization**: Efficient parameter handling
4. **Real-Time Scheduling**: Automatic refresh management

## Extension Points

### Custom Repository

```php
class CustomAnalyticsRepository implements AnalyticsRepositoryInterface
{
    // Custom implementation
}

// Register in service provider
$this->app->bind(AnalyticsRepositoryInterface::class, CustomAnalyticsRepository::class);
```

### Custom Cache Manager

```php
class RedisCacheManager implements CacheManagerInterface
{
    // Custom implementation
}

// Register in service provider
$this->app->bind(CacheManagerInterface::class, RedisCacheManager::class);
```

### Event Listeners

```php
Event::listen(AnalyticsDataFetched::class, function ($event) {
    // Custom logic
});
```

## Testing Strategy

### Unit Tests

Mock interfaces for isolated testing:

```php
$repository = Mockery::mock(AnalyticsRepositoryInterface::class);
$cacheManager = Mockery::mock(CacheManagerInterface::class);
$service = new AnalyticsService($repository, $cacheManager, $transformer);
```

### Integration Tests

Test with real implementations:

```php
$service = app(AnalyticsService::class);
$result = $service->runReports(...);
```

## Future Enhancements

1. **Query Builder**: Fluent interface for building queries
2. **Batch Operations**: Process multiple queries efficiently
3. **WebSocket Support**: Real-time push updates
4. **Analytics Dashboard**: Built-in visualization
5. **Export Functionality**: Export data to various formats

