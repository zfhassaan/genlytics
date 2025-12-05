<?php

namespace zfhassaan\genlytics\provider;

use Illuminate\Support\ServiceProvider;
use zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface;
use zfhassaan\genlytics\Contracts\CacheManagerInterface;
use zfhassaan\genlytics\Contracts\DataTransformerInterface;
use zfhassaan\genlytics\Events\AnalyticsCacheUpdated;
use zfhassaan\genlytics\Genlytics;
use zfhassaan\genlytics\Listeners\UpdateRealTimeCache;
use zfhassaan\genlytics\overrides\BetaAnalyticsDataClient;
use zfhassaan\genlytics\Repositories\AnalyticsRepository;
use zfhassaan\genlytics\Services\AnalyticsService;
use zfhassaan\genlytics\Services\CacheManager;
use zfhassaan\genlytics\Services\DataTransformer;

/**
 * Analytics Service Provider
 * Registers all services and bindings following Dependency Inversion Principle
 */
class AnalyticsServiceProvider extends ServiceProvider
{
    /**
     * Register the application services
     */
    public function register(): void
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../../config/analytics.php', 'analytics');

        // Register Cache Manager
        $this->app->singleton(CacheManagerInterface::class, function ($app) {
            $lifetime = config('analytics.cache_lifetime_in_minutes', 1440) * 60; // Convert to seconds
            return new CacheManager('genlytics', $lifetime);
        });

        // Register Data Transformer
        $this->app->singleton(DataTransformerInterface::class, DataTransformer::class);

        // Register Analytics Repository
        $this->app->singleton(AnalyticsRepositoryInterface::class, function ($app) {
            $propertyId = 'properties/' . config('analytics.property_id');
            $client = new BetaAnalyticsDataClient();
            return new AnalyticsRepository($client, $propertyId);
        });

        // Register Analytics Service
        $this->app->singleton(AnalyticsService::class, function ($app) {
            return new AnalyticsService(
                $app->make(AnalyticsRepositoryInterface::class),
                $app->make(CacheManagerInterface::class),
                $app->make(DataTransformerInterface::class),
                config('analytics.use_background_jobs', true),
                config('analytics.enable_cache', true),
                config('analytics.enable_realtime_updates', true)
            );
        });

        // Register main Genlytics class (maintains backward compatibility)
        $this->app->singleton('genlytics', function ($app) {
            return new Genlytics($app->make(AnalyticsService::class));
        });

        // Alias for facade
        $this->app->alias('genlytics', Genlytics::class);
    }

    /**
     * Bootstrap the application services
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/analytics.php' => config_path('analytics.php'),
            ], 'config');

            // Register commands
            $this->commands([
                \zfhassaan\genlytics\Commands\RefreshAnalyticsCache::class,
            ]);
        }

        // Register event listeners
        if (config('analytics.enable_realtime_updates', true)) {
            $this->app['events']->listen(
                AnalyticsCacheUpdated::class,
                UpdateRealTimeCache::class
            );
        }
    }
}
