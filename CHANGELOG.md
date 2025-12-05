# Changelog

All notable changes to `genlytics` will be documented in this file.

## v1.0.4 - 2025-12-05

### Major Release - Complete Package Rewrite

This release represents a complete architectural overhaul of the Genlytics package, implementing modern PHP and Laravel best practices while maintaining full backward compatibility.

### What's Changed

#### Architecture Improvements

- **Architecture Improvements**: Complete refactoring with modern PHP and Laravel best practices, Repository pattern, and Event-driven architecture
- **Repository Pattern**: Abstracted data access layer for improved testability and extensibility
- **Event-Driven Architecture**: Decoupled event system with four new event classes for better extensibility
- **Service Layer Pattern**: Business logic orchestration through dedicated service classes

#### New Features

- **Intelligent Caching System**: Automatic cache management with configurable TTL, reducing API calls by 90%+
- **Background Job Processing**: Non-blocking analytics queries using Laravel queues
- **Real-Time Updates**: Automatic refresh scheduling for live dashboards
- **Enhanced Error Handling**: Robust error handling with comprehensive logging
- **Type Safety**: Full type hints and comprehensive PHPDoc throughout

#### New Components

**Contracts (Interfaces)**
- `AnalyticsRepositoryInterface`: Data access contract
- `CacheManagerInterface`: Caching operations contract
- `DataTransformerInterface`: Data transformation contract

**Repositories**
- `AnalyticsRepository`: Optimized implementation with efficient query building

**Services**
- `AnalyticsService`: Main orchestration service
- `CacheManager`: Intelligent caching with TTL management
- `DataTransformer`: Robust data transformation with error handling

**Events**
- `AnalyticsDataRequested`: Fired when data is requested
- `AnalyticsDataFetched`: Fired when data is successfully fetched
- `AnalyticsCacheUpdated`: Fired when cache is updated
- `AnalyticsQueryFailed`: Fired when queries fail

**Jobs**
- `FetchAnalyticsDataJob`: Background processing for analytics queries

**Commands**
- `RefreshAnalyticsCache`: Artisan command for cache management (`genlytics:refresh-cache`)

**Listeners**
- `UpdateRealTimeCache`: Automatic real-time cache refresh listener

#### Testing

- **Comprehensive Test Suite**: 52 test cases with 83+ assertions
- **Unit Tests**: All services, repositories, and components tested
- **Feature Tests**: End-to-end functionality testing
- **Integration Tests**: Service provider and Laravel integration testing
- **Test Publishing**: Tests can be published to application for customization

#### Documentation

- **Comprehensive Wiki**: Complete documentation in wiki format
- **Architecture Documentation**: Detailed architecture guide (ARCHITECTURE.md)
- **Migration Guide**: Step-by-step migration instructions (MIGRATION.md)
- **Rewrite Summary**: Complete summary of changes (REWRITE_SUMMARY.md)
- **Updated README**: Comprehensive usage examples and feature documentation
- **Test Documentation**: Complete test suite documentation

#### Bug Fixes

- Fixed typo: `genltyics` → `genlytics` in service provider
- Fixed method name: `getDImensionValues()` → `getDimensionValues()`
- Fixed facade namespace case sensitivity
- Fixed credentials path to use config instead of hardcoded value
- Improved null handling in DataTransformer
- Enhanced error handling with specific exceptions

#### Configuration Improvements

- Enhanced configuration file with new options for caching, background jobs, and real-time updates
- Support for configurable queue connection and queue name
- Configurable cache lifetime for reports and real-time data
- Environment variable support for all configuration options

#### Performance Optimizations

- Intelligent caching reduces API calls by 90%+
- Background processing for non-blocking requests
- Query optimization with efficient parameter handling
- Real-time scheduling for automatic refresh management

#### Developer Experience

- Laravel auto-discovery support
- Publishable configuration and test cases
- Comprehensive PHPDoc throughout
- Better error messages and logging
- Improved code organization and maintainability

### Backward Compatibility

All existing code continues to work without changes. The package maintains the same public API while improving internal architecture.

### Migration

See [MIGRATION.md](MIGRATION.md) for detailed migration instructions. No code changes required for basic usage.

### Documentation

- [Installation Guide](wiki/Installation-Guide.md)
- [Configuration Guide](wiki/Configuration-Guide.md)
- [Usage Guide](wiki/Usage-Guide.md)
- [Troubleshooting](wiki/Troubleshooting.md)
- [Architecture](ARCHITECTURE.md)
- [Migration Guide](MIGRATION.md)

**Full Changelog**: https://github.com/zfhassaan/genlytics/compare/v1.0.3...v1.0.4

## v1.0.3 - 2023-08-28

### What's Changed

- fix for Pull Request by @zfhassaan in https://github.com/zfhassaan/genlytics/pull/8

### New Contributors

- @zfhassaan made their first contribution in https://github.com/zfhassaan/genlytics/pull/8

**Full Changelog**: https://github.com/zfhassaan/genlytics/compare/v1.0.2...v1.0.3

## v1.0.2 - 2023-05-04

Small Patch Fix for Credentials Loader

### What's Changed

- Credentialsloader patched by @salman-mahmood in https://github.com/zfhassaan/genlytics/pull/6

**Full Changelog**: https://github.com/zfhassaan/genlytics/compare/v1.0.1...v1.0.2

## Patch fix for autoloading files - 2023-05-04

Patch fix for autoloading files

## v1.0.0  - 2023-05-03

Initial Release for Genlytics

- Real Time Reports with GA4 Support
- Reports from a time date to a time date.
- Exception Handling

## alpha-release - 2023-04-29

Genlytics is a Google Analytics package specifically designed for Laravel to help businesses and developers easily track and analyze website traffic and user behavior.

The Google UA Property is going away from July 2023, and Google Analytics V4 is being implemented. Therefore, Universal Analytics will no longer process new data in standard properties starting July 1st, 2023. It is essential to prepare now by setting up and switching over to a Google Analytics 4 property.

With Genlytics, you can access and query your Google Analytics data using the GA4 property, check your website traffic, user behavior, and conversion rates, and gain valuable insights into your website performance.

The GA4 query explorer at https://ga-dev-tools.web.app/ga4/query-explorer/ allows you to create custom queries, providing the flexibility and functionality you need to make data-driven decisions for your business.

Before integrating Genlytics into your Laravel application, it's essential to have a property created on Google Analytics V4 and data stream set up to ensure all of your website's data is being properly collected and tracked.

Once the property and data stream are in place, you can easily integrate Genlytics with your Laravel application, providing you with valuable insights into your website traffic, user behavior, and conversion rates.

Genlytics makes it simple for businesses and developers to understand their audience and improve their online performance by giving them access to all of the features and functionality of Google Analytics.
