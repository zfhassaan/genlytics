<?php

namespace zfhassaan\genlytics\provider;

use Illuminate\Support\ServiceProvider;
use zfhassaan\genlytics\Contracts\AnalyticsRepositoryInterface;
use zfhassaan\genlytics\Contracts\CacheManagerInterface;
use zfhassaan\genlytics\Contracts\DataTransformerInterface;
use zfhassaan\genlytics\Contracts\QuotaManagerInterface;
use zfhassaan\genlytics\Events\AnalyticsCacheUpdated;
use zfhassaan\genlytics\Genlytics;
use zfhassaan\genlytics\Listeners\UpdateRealTimeCache;
use zfhassaan\genlytics\overrides\BetaAnalyticsDataClient;
use zfhassaan\genlytics\Repositories\AnalyticsRepository;
use zfhassaan\genlytics\Services\AnalyticsService;
use zfhassaan\genlytics\Services\CacheManager;
use zfhassaan\genlytics\Services\DataTransformer;
use zfhassaan\genlytics\Services\QuotaManager;
use Illuminate\Support\Facades\Cache;

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
            $prefix = config('analytics.cache.prefix', 'genlytics');
            $store = config('analytics.cache.store');
            $useTags = (bool) config('analytics.cache.use_tags', true);

            $ttls = config('analytics.cache.ttl', []);
            if (!is_array($ttls)) {
                $ttls = [];
            }

            $legacyMinutes = config('analytics.cache_lifetime_in_minutes');
            if ($legacyMinutes !== null && (int) $legacyMinutes !== 1440) {
                $ttls['default'] = (int) $legacyMinutes * 60;
            } elseif ($defaultTtl = config('analytics.cache.default_ttl')) {
                $ttls['default'] = (int) $defaultTtl;
            } else {
                $ttls['default'] = 86400;
            }

            return new CacheManager($prefix, $ttls, $store, $useTags);
        });

        // Register Data Transformer
        $this->app->singleton(DataTransformerInterface::class, DataTransformer::class);

        // Register Quota Manager
        $this->app->singleton(QuotaManagerInterface::class, function ($app) {
            $cacheStore = config('analytics.cache.store');
            $cache = $cacheStore ? Cache::store($cacheStore) : Cache::store();
            $quotaConfig = (array) config('analytics.quota', []);

            return new QuotaManager($cache, $quotaConfig);
        });
        $this->app->alias(QuotaManagerInterface::class, QuotaManager::class);

        // Register Analytics Repository
        $this->app->singleton(AnalyticsRepositoryInterface::class, function ($app) {
            $propertyId = 'properties/' . config('analytics.property_id');
            $credentials = config('analytics.service_account_credentials_json');

            $clientOptions = [];
            if ($credentials) {
                if (is_array($credentials) || (is_string($credentials) && file_exists($credentials))) {
                    $clientOptions['credentials'] = $credentials;
                }
            }

            $transport = config('analytics.transport', 'rest');
            if ($transport) {
                $clientOptions['transport'] = $transport;
            }

            $client = new BetaAnalyticsDataClient($clientOptions);
            $quotaManager = $app->make(QuotaManagerInterface::class);
            return new AnalyticsRepository($client, $propertyId, $quotaManager);
        });

        // Register Analytics Service
        $this->app->singleton(AnalyticsService::class, function ($app) {
            $useBackgroundJobs = (bool) config('analytics.queue.enabled', config('analytics.use_background_jobs', false));
            $enableCache = (bool) config('analytics.cache.enabled', config('analytics.enable_cache', true));
            $enableRealtime = (bool) config('analytics.enable_realtime_updates', false);
            $lockDuration = (int) config('analytics.cache.lock_duration', 60);

            return new AnalyticsService(
                $app->make(AnalyticsRepositoryInterface::class),
                $app->make(CacheManagerInterface::class),
                $app->make(DataTransformerInterface::class),
                $useBackgroundJobs,
                $enableCache,
                $enableRealtime,
                $lockDuration,
                $app->make(QuotaManagerInterface::class)
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
            // Publish configuration
            $this->publishes([
                __DIR__ . '/../../config/analytics.php' => config_path('analytics.php'),
            ], ['genlytics-config', 'config']);

            // Publish test cases
            $this->publishes([
                __DIR__ . '/../../tests/TestCase.stub' => base_path('tests/Genlytics/TestCase.php'),
                __DIR__ . '/../../tests/Unit' => base_path('tests/Genlytics/Unit'),
                __DIR__ . '/../../tests/Feature' => base_path('tests/Genlytics/Feature'),
                __DIR__ . '/../../tests/Integration' => base_path('tests/Genlytics/Integration'),
                __DIR__ . '/../../tests/README.md' => base_path('tests/Genlytics/README.md'),
                __DIR__ . '/../../tests/PUBLISHING.md' => base_path('tests/Genlytics/PUBLISHING.md'),
            ], ['genlytics-tests', 'tests']);

            // Register commands
            $this->commands([
                \zfhassaan\genlytics\Commands\RefreshAnalyticsCache::class,
                \zfhassaan\genlytics\Commands\ConsumeDailyQuota::class,
            ]);
        }

        // Register event listeners
        if (config('analytics.enable_realtime_updates', false)) {
            $this->app['events']->listen(
                AnalyticsCacheUpdated::class,
                UpdateRealTimeCache::class
            );
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'genlytics',
            Genlytics::class,
            AnalyticsService::class,
            AnalyticsRepositoryInterface::class,
            CacheManagerInterface::class,
            DataTransformerInterface::class,
        ];
    }
}
