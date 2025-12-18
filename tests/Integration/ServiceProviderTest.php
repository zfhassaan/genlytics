<?php

namespace zfhassaan\genlytics\Tests\Integration;

use zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface;
use zfhassaan\genlytics\Contracts\CacheManagerInterface;
use zfhassaan\genlytics\Contracts\DataTransformerInterface;
use zfhassaan\genlytics\Genlytics;
use zfhassaan\genlytics\Services\AnalyticsService;
use zfhassaan\genlytics\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_all_services_are_registered()
    {
        // Skip if credentials are not available (for CI environments)
        if (!file_exists(config('analytics.service_account_credentials_json'))) {
            $this->markTestSkipped('Service account credentials not available for testing');
        }

        $this->assertInstanceOf(
            CacheManagerInterface::class,
            app(CacheManagerInterface::class)
        );

        $this->assertInstanceOf(
            DataTransformerInterface::class,
            app(DataTransformerInterface::class)
        );

        $this->assertInstanceOf(
            AnalyticsRepositoryInterface::class,
            app(AnalyticsRepositoryInterface::class)
        );

        $this->assertInstanceOf(
            AnalyticsService::class,
            app(AnalyticsService::class)
        );

        $this->assertInstanceOf(
            Genlytics::class,
            app('genlytics')
        );
    }

    public function test_configuration_is_published()
    {
        $this->assertTrue(config('analytics.property_id') !== null);
        $this->assertTrue(config('analytics.enable_cache') !== null);
    }

    public function test_cache_manager_has_correct_lifetime()
    {
        $cacheManager = app(CacheManagerInterface::class);

        $expectedLifetime = config('analytics.cache_lifetime_in_minutes', 1440) * 60;
        $this->assertEquals($expectedLifetime, $cacheManager->getLifetime());
    }
}
