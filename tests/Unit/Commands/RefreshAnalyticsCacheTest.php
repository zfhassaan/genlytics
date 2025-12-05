<?php

namespace zfhassaan\genlytics\Tests\Unit\Commands;

use Mockery;
use zfhassaan\genlytics\Commands\RefreshAnalyticsCache;
use zfhassaan\genlytics\Contracts\CacheManagerInterface;
use zfhassaan\genlytics\Tests\TestCase;

class RefreshAnalyticsCacheTest extends TestCase
{
    protected $mockCacheManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockCacheManager = Mockery::mock(CacheManagerInterface::class);
        $this->app->instance(CacheManagerInterface::class, $this->mockCacheManager);
    }

    public function test_can_clear_cache()
    {
        $this->mockCacheManager
            ->shouldReceive('clear')
            ->once()
            ->andReturn(true);

        $this->artisan('genlytics:refresh-cache', ['--clear' => true])
            ->expectsOutput('Analytics cache cleared successfully.')
            ->assertExitCode(0);
    }

    public function test_can_refresh_all_cache()
    {
        $this->artisan('genlytics:refresh-cache')
            ->expectsOutput('Refreshing all analytics cache...')
            ->expectsOutput('Cache refresh queued. Use background jobs to refresh data.')
            ->assertExitCode(0);
    }

    public function test_can_refresh_specific_types()
    {
        $this->artisan('genlytics:refresh-cache', [
            '--type' => ['report', 'realtime']
        ])
            ->expectsOutput('Refreshing report cache...')
            ->expectsOutput('Refreshing realtime cache...')
            ->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

