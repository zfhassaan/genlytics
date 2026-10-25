<?php

namespace zfhassaan\genlytics\Tests\Unit\Commands;

use Mockery;
use zfhassaan\genlytics\Contracts\QuotaManagerInterface;
use zfhassaan\genlytics\DTOs\QuotaStatusDTO;
use zfhassaan\genlytics\Genlytics;
use zfhassaan\genlytics\Tests\TestCase;

class ConsumeDailyQuotaTest extends TestCase
{
    protected $mockQuota;
    protected $mockGenlytics;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockQuota = Mockery::mock(QuotaManagerInterface::class);
        $this->mockGenlytics = Mockery::mock(Genlytics::class);

        $this->app->instance(QuotaManagerInterface::class, $this->mockQuota);
        $this->app->instance(Genlytics::class, $this->mockGenlytics);
        $this->app->instance('genlytics', $this->mockGenlytics);
    }

    public function test_consume_quota_dry_run_mode()
    {
        $status = new QuotaStatusDTO([
            'property_type' => 'standard',
            'mode' => 'consume_all',
            'daily_tokens_limit' => 200000,
            'daily_tokens_consumed' => 20000,
            'seconds_until_daily_reset' => 45000,
            'daily_reset_at_pst' => '00:00:00 PST',
        ]);

        $this->mockQuota->shouldReceive('enableAllQuotaConsumption')->once()->andReturnSelf();
        $this->mockQuota->shouldReceive('getStatus')->once()->andReturn($status);

        $this->artisan('genlytics:consume-quota', ['--dry-run' => true, '--target-utilization' => 50])
            ->expectsOutput('DRY RUN: Pre-flight estimation complete. Exiting without calling Google API.')
            ->assertExitCode(0);
    }

    public function test_consume_quota_when_already_at_or_above_target()
    {
        $status = new QuotaStatusDTO([
            'property_type' => 'standard',
            'mode' => 'consume_all',
            'daily_tokens_limit' => 200000,
            'daily_tokens_consumed' => 180000,
            'seconds_until_daily_reset' => 45000,
            'daily_reset_at_pst' => '00:00:00 PST',
        ]);

        $this->mockQuota->shouldReceive('enableAllQuotaConsumption')->once()->andReturnSelf();
        $this->mockQuota->shouldReceive('getStatus')->once()->andReturn($status);

        $this->artisan('genlytics:consume-quota', ['--target-utilization' => 50])
            ->expectsOutput('✓ Target utilization (50%) already reached. No additional tokens needed.')
            ->assertExitCode(0);
    }

    public function test_consume_quota_executes_prewarming_tasks()
    {
        $initialStatus = new QuotaStatusDTO([
            'property_type' => 'standard',
            'mode' => 'consume_all',
            'daily_tokens_limit' => 200000,
            'daily_tokens_consumed' => 199990,
            'seconds_until_daily_reset' => 45000,
            'daily_reset_at_pst' => '00:00:00 PST',
        ]);

        $finalStatus = new QuotaStatusDTO([
            'property_type' => 'standard',
            'mode' => 'consume_all',
            'daily_tokens_limit' => 200000,
            'daily_tokens_consumed' => 200000,
            'seconds_until_daily_reset' => 45000,
            'daily_reset_at_pst' => '00:00:00 PST',
        ]);

        $this->mockQuota->shouldReceive('enableAllQuotaConsumption')->once()->andReturnSelf();
        $this->mockQuota->shouldReceive('getStatus')->andReturn($initialStatus, $finalStatus);
        $this->mockQuota->shouldReceive('getRemainingDailyTokens')->andReturn(10, 8, 6, 4, 0);
        $this->mockQuota->shouldReceive('acquireConcurrencySlot')->andReturn(true);
        $this->mockQuota->shouldReceive('releaseConcurrencySlot')->atLeast()->once();

        $this->mockGenlytics->shouldReceive('topProducts')->atLeast()->once()->andReturn(collect([]));

        $this->artisan('genlytics:consume-quota', ['--target-utilization' => 100])
            ->expectsOutput('✓ Daily Quota Pre-Warming Completed Successfully!')
            ->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

