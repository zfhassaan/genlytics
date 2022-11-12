<?php

namespace zfhassaan\genlytics;

use Illuminate\Support\ServiceProvider;

class AnalyticsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application Services.
     *
     */
    public function boot(){
        if($this->app->runningInConsole())
        {
            $this->publishes([
                __DIR__.'/../config/analytics.php'  => config_path('analytics.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/analytics.php', 'analytics');

        // Register the main class to use with the facade
        $this->app->singleton('genltyics', function () {
            return new Genlytics;
        });
    }
}
