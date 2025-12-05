# Publishing Genlytics Tests

This guide explains how to publish and use Genlytics test cases in your Laravel application.

## Publishing Tests

To publish the test cases to your Laravel application:

```bash
php artisan vendor:publish --tag=genlytics-tests
```

This will publish the test files to:
- `tests/Genlytics/TestCase.php` - Base test case
- `tests/Genlytics/Unit/` - Unit tests
- `tests/Genlytics/Feature/` - Feature tests
- `tests/Genlytics/Integration/` - Integration tests
- `tests/Genlytics/README.md` - Test documentation

## Updating Namespaces

After publishing, you may need to update the namespaces in the test files:

1. **TestCase.php**: Already uses `Tests\Genlytics` namespace (correct for Laravel apps)

2. **Test Files**: Update namespace from `zfhassaan\genlytics\Tests\...` to `Tests\Genlytics\...`

   Example:
   ```php
   // Before
   namespace zfhassaan\genlytics\Tests\Unit\Services;
   
   // After
   namespace Tests\Genlytics\Unit\Services;
   ```

3. **Update TestCase import**: Change the import in test files:
   ```php
   // Before
   use zfhassaan\genlytics\Tests\TestCase;
   
   // After
   use Tests\Genlytics\TestCase;
   ```

## Running Published Tests

After publishing and updating namespaces, run the tests:

```bash
# Run all Genlytics tests
php artisan test tests/Genlytics

# Run specific test suite
php artisan test tests/Genlytics/Unit
php artisan test tests/Genlytics/Feature
php artisan test tests/Genlytics/Integration

# Run specific test file
php artisan test tests/Genlytics/Unit/Services/CacheManagerTest.php
```

## Customizing Tests

You can customize the published tests to:

- Add your own test cases
- Modify existing tests for your use case
- Add integration with your application's specific features
- Extend test coverage

## Test Fixtures

If your tests require fixtures (like service account JSON), create:

```
tests/Genlytics/fixtures/
  └── service-account.json
```

**Note**: Never commit real credentials to version control. Use dummy/test credentials for testing.

## Example: Custom Test

```php
<?php

namespace Tests\Genlytics\Unit\Custom;

use Tests\Genlytics\TestCase;
use zfhassaan\genlytics\Genlytics;

class CustomAnalyticsTest extends TestCase
{
    public function test_custom_functionality()
    {
        $analytics = app('genlytics');
        
        // Your custom test logic
        $this->assertInstanceOf(Genlytics::class, $analytics);
    }
}
```

## Notes

- Published tests are independent of the package tests
- You can modify published tests without affecting package updates
- Keep your custom tests in sync with package updates if needed
- The TestCase base class handles all package setup automatically

