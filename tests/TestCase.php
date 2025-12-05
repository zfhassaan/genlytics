<?php

namespace zfhassaan\genlytics\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use zfhassaan\genlytics\provider\AnalyticsServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set default config values for testing
        $this->app['config']->set('analytics.property_id', '123456789');
        $this->app['config']->set('analytics.service_account_credentials_json', __DIR__ . '/fixtures/service-account.json');
        $this->app['config']->set('analytics.enable_cache', true);
        $this->app['config']->set('analytics.cache_lifetime_in_minutes', 60);
        $this->app['config']->set('analytics.use_background_jobs', false);
        $this->app['config']->set('analytics.enable_realtime_updates', false);
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            AnalyticsServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<string, class-string<\Illuminate\Support\Facades\Facade>>
     */
    protected function getPackageAliases($app)
    {
        return [
            'Genlytics' => \zfhassaan\genlytics\facades\AnalyticsFacade::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup cache
        $app['config']->set('cache.default', 'array');
    }
}

