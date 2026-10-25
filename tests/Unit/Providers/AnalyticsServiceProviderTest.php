<?php

namespace zfhassaan\genlytics\Tests\Unit\Providers;

use Illuminate\Support\Facades\Event;
use zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface;
use zfhassaan\genlytics\Contracts\CacheManagerInterface;
use zfhassaan\genlytics\Contracts\DataTransformerInterface;
use zfhassaan\genlytics\Contracts\QuotaManagerInterface;
use zfhassaan\genlytics\Events\AnalyticsCacheUpdated;
use zfhassaan\genlytics\Genlytics;
use zfhassaan\genlytics\provider\AnalyticsServiceProvider;
use zfhassaan\genlytics\Services\AnalyticsService;
use zfhassaan\genlytics\Services\CacheManager;
use zfhassaan\genlytics\Services\DataTransformer;
use zfhassaan\genlytics\Services\QuotaManager;
use zfhassaan\genlytics\Tests\TestCase;

class AnalyticsServiceProviderTest extends TestCase
{
    public function test_service_provider_provides_correct_services()
    {
        $provider = new AnalyticsServiceProvider($this->app);
        $provides = $provider->provides();

        $this->assertContains('genlytics', $provides);
        $this->assertContains(Genlytics::class, $provides);
        $this->assertContains(AnalyticsService::class, $provides);
        $this->assertContains(AnalyticsRepositoryInterface::class, $provides);
        $this->assertContains(CacheManagerInterface::class, $provides);
        $this->assertContains(DataTransformerInterface::class, $provides);
    }

    public function test_service_provider_registers_cache_manager_singleton()
    {
        config(['analytics.cache.prefix' => 'test-pkg']);
        config(['analytics.cache.store' => null]);
        config(['analytics.cache.ttl' => ['pages' => 3600]]);
        config(['analytics.cache_lifetime_in_minutes' => 60]);

        $cacheManager = $this->app->make(CacheManagerInterface::class);

        $this->assertInstanceOf(CacheManagerInterface::class, $cacheManager);
        $this->assertInstanceOf(CacheManager::class, $cacheManager);
        $this->assertEquals(3600, $cacheManager->getLifetime('default'));
    }

    public function test_service_provider_registers_data_transformer_singleton()
    {
        $transformer = $this->app->make(DataTransformerInterface::class);

        $this->assertInstanceOf(DataTransformerInterface::class, $transformer);
        $this->assertInstanceOf(DataTransformer::class, $transformer);
    }

    public function test_service_provider_registers_quota_manager_singleton_and_alias()
    {
        $quotaManager = $this->app->make(QuotaManagerInterface::class);

        $this->assertInstanceOf(QuotaManagerInterface::class, $quotaManager);
        $this->assertInstanceOf(QuotaManager::class, $quotaManager);

        $aliasManager = $this->app->make(QuotaManager::class);
        $this->assertSame($quotaManager, $aliasManager);
    }

    public function test_service_provider_registers_repository_service_and_genlytics()
    {
        $mockRepo = \Mockery::mock(AnalyticsRepositoryInterface::class);
        $this->app->instance(AnalyticsRepositoryInterface::class, $mockRepo);

        $service = $this->app->make(AnalyticsService::class);
        $this->assertInstanceOf(AnalyticsService::class, $service);

        $genlytics = $this->app->make('genlytics');
        $this->assertInstanceOf(Genlytics::class, $genlytics);

        $alias = $this->app->make(Genlytics::class);
        $this->assertSame($genlytics, $alias);
    }

    public function test_service_provider_boot_in_console()
    {
        $provider = new AnalyticsServiceProvider($this->app);
        $provider->boot();

        $this->assertTrue(true);
    }

    public function test_service_provider_boot_with_realtime_listener()
    {
        config(['analytics.enable_realtime_updates' => true]);

        $provider = new AnalyticsServiceProvider($this->app);
        $provider->boot();

        $listeners = $this->app['events']->getListeners(AnalyticsCacheUpdated::class);
        $this->assertNotEmpty($listeners);
    }
}
