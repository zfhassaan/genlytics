<?php

namespace zfhassaan\genlytics\Tests\Feature;

use Illuminate\Support\Facades\Event;
use zfhassaan\genlytics\Events\AnalyticsDataRequested;
use zfhassaan\genlytics\Genlytics;
use zfhassaan\genlytics\Tests\TestCase;

class GenlyticsIntegrationTest extends TestCase
{
    public function test_service_provider_registers_genlytics()
    {
        // Skip if credentials are not available (for CI environments)
        if (!file_exists(config('analytics.service_account_credentials_json'))) {
            $this->markTestSkipped('Service account credentials not available for testing');
        }

        $genlytics = app('genlytics');

        $this->assertInstanceOf(Genlytics::class, $genlytics);
    }

    public function test_facade_works()
    {
        // Skip if credentials are not available (for CI environments)
        if (!file_exists(config('analytics.service_account_credentials_json'))) {
            $this->markTestSkipped('Service account credentials not available for testing');
        }

        $genlytics = \Genlytics::getFacadeRoot();

        $this->assertInstanceOf(Genlytics::class, $genlytics);
    }

    public function test_configuration_is_loaded()
    {
        $propertyId = config('analytics.property_id');

        $this->assertNotNull($propertyId);
        $this->assertEquals('123456789', $propertyId);
    }

    public function test_cache_manager_is_bound()
    {
        $cacheManager = app(\zfhassaan\genlytics\Contracts\CacheManagerInterface::class);

        $this->assertInstanceOf(\zfhassaan\genlytics\Contracts\CacheManagerInterface::class, $cacheManager);
    }

    public function test_data_transformer_is_bound()
    {
        $transformer = app(\zfhassaan\genlytics\Contracts\DataTransformerInterface::class);

        $this->assertInstanceOf(\zfhassaan\genlytics\Contracts\DataTransformerInterface::class, $transformer);
    }

    public function test_repository_is_bound()
    {
        // Skip if credentials are not available (for CI environments)
        if (!file_exists(config('analytics.service_account_credentials_json'))) {
            $this->markTestSkipped('Service account credentials not available for testing');
        }

        $repository = app(\zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface::class);

        $this->assertInstanceOf(\zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface::class, $repository);
    }

    public function test_analytics_service_is_bound()
    {
        // Skip if credentials are not available (for CI environments)
        if (!file_exists(config('analytics.service_account_credentials_json'))) {
            $this->markTestSkipped('Service account credentials not available for testing');
        }

        $service = app(\zfhassaan\genlytics\Services\AnalyticsService::class);

        $this->assertInstanceOf(\zfhassaan\genlytics\Services\AnalyticsService::class, $service);
    }
}
