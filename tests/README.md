# Genlytics Test Suite

This directory contains comprehensive test cases for the Genlytics package.

## Publishing Tests to Your Application

You can publish these test cases to your Laravel application for customization:

```bash
php artisan vendor:publish --tag=genlytics-tests
```

See [PUBLISHING.md](PUBLISHING.md) for detailed instructions on publishing and using tests in your application.

## Test Structure

```
tests/
├── TestCase.php                    # Base test case with setup
├── Unit/                           # Unit tests
│   ├── Services/
│   │   ├── CacheManagerTest.php
│   │   ├── DataTransformerTest.php
│   │   └── AnalyticsServiceTest.php
│   ├── Repositories/
│   │   └── AnalyticsRepositoryTest.php
│   ├── Events/
│   │   └── AnalyticsEventsTest.php
│   ├── Commands/
│   │   └── RefreshAnalyticsCacheTest.php
│   ├── Jobs/
│   │   └── FetchAnalyticsDataJobTest.php
│   ├── Listeners/
│   │   └── UpdateRealTimeCacheTest.php
│   └── GenlyticsTest.php
├── Feature/                        # Feature tests
│   └── GenlyticsIntegrationTest.php
└── Integration/                    # Integration tests
    └── ServiceProviderTest.php
```

## Running Tests

### Run All Tests

```bash
composer test
```

or

```bash
vendor/bin/phpunit
```

### Run Specific Test Suite

```bash
# Unit tests only
vendor/bin/phpunit tests/Unit

# Feature tests only
vendor/bin/phpunit tests/Feature

# Integration tests only
vendor/bin/phpunit tests/Integration
```

### Run Specific Test File

```bash
vendor/bin/phpunit tests/Unit/Services/CacheManagerTest.php
```

### Run Specific Test Method

```bash
vendor/bin/phpunit --filter test_can_put_and_get_cache
```

### With Coverage

```bash
composer test-coverage
```

Coverage report will be generated in `coverage/` directory.

## Test Coverage

The test suite covers:

- ✅ **Services**: CacheManager, DataTransformer, AnalyticsService
- ✅ **Repositories**: AnalyticsRepository with mocked Google API client
- ✅ **Events**: All event classes
- ✅ **Jobs**: FetchAnalyticsDataJob
- ✅ **Commands**: RefreshAnalyticsCache command
- ✅ **Listeners**: UpdateRealTimeCache listener
- ✅ **Main Class**: Genlytics facade wrapper
- ✅ **Integration**: Service provider registration
- ✅ **Feature**: End-to-end functionality

## Writing New Tests

### Unit Test Example

```php
<?php

namespace zfhassaan\genlytics\Tests\Unit\Services;

use zfhassaan\genlytics\Tests\TestCase;

class MyServiceTest extends TestCase
{
    public function test_something()
    {
        // Arrange
        $service = new MyService();
        
        // Act
        $result = $service->doSomething();
        
        // Assert
        $this->assertNotNull($result);
    }
}
```

### Mocking Dependencies

```php
use Mockery;
use zfhassaan\genlytics\Contracts\SomeInterface;

$mock = Mockery::mock(SomeInterface::class);
$mock->shouldReceive('method')->once()->andReturn('value');
```

### Testing Events

```php
use Illuminate\Support\Facades\Event;
use zfhassaan\genlytics\Events\SomeEvent;

Event::fake();

// Your code that fires event

Event::assertDispatched(SomeEvent::class);
```

### Testing Jobs

```php
use Illuminate\Support\Facades\Queue;
use zfhassaan\genlytics\Jobs\SomeJob;

Queue::fake();

// Your code that dispatches job

Queue::assertPushed(SomeJob::class);
```

## Test Configuration

Test configuration is in `phpunit.xml`. Key settings:

- **Bootstrap**: `vendor/autoload.php`
- **Cache Directory**: `.phpunit.cache`
- **Test Suites**: Unit, Feature, Integration
- **Environment**: `APP_ENV=testing`, `CACHE_DRIVER=array`, `QUEUE_CONNECTION=sync`

## Continuous Integration

Tests are configured to run in CI/CD pipelines. See `.github/workflows/run-tests.yml` for GitHub Actions configuration.

## Notes

- All tests use mocks for external dependencies (Google API)
- Tests don't require actual Google Analytics credentials
- Cache uses array driver for fast test execution
- Queue uses sync driver to avoid async complexity in tests

