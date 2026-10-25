<?php

namespace zfhassaan\genlytics\Tests\Unit\Services;

use Carbon\Carbon;
use Google\Analytics\Data\V1beta\PropertyQuota;
use Google\Analytics\Data\V1beta\QuotaStatus;
use Illuminate\Support\Facades\Cache;
use zfhassaan\genlytics\DTOs\QuotaStatusDTO;
use zfhassaan\genlytics\Services\QuotaManager;
use zfhassaan\genlytics\Tests\TestCase;

class QuotaManagerTest extends TestCase
{
    protected QuotaManager $quotaManager;

    protected function setUp(): void
    {
        parent::setUp();

        $cache = Cache::store('array');

        $this->quotaManager = new QuotaManager($cache, [
            'property_type' => 'standard',
            'daily_token_limit' => 200000,
            'hourly_token_limit' => 40000,
            'concurrent_requests_limit' => 10,
            'project_daily_request_limit' => 50000,
            'ip_qps_limit' => 10,
            'user_requests_per_100s' => 100,
            'timezone' => 'America/Los_Angeles',
            'warning_threshold' => 0.85,
        ]);

        $this->quotaManager->reset();
    }

    public function test_initial_status_has_full_tokens_and_optimal_pacing()
    {
        $status = $this->quotaManager->getStatus();

        $this->assertInstanceOf(QuotaStatusDTO::class, $status);
        $this->assertEquals('standard', $status->propertyType);
        $this->assertEquals(200000, $status->dailyTokensLimit);
        $this->assertEquals(0, $status->dailyTokensConsumed);
        $this->assertEquals(200000, $status->dailyTokensRemaining);
        $this->assertEquals(0.0, $status->dailyUtilizationPercent);

        $this->assertEquals(40000, $status->hourlyTokensLimit);
        $this->assertEquals(0, $status->hourlyTokensConsumed);
        $this->assertEquals(40000, $status->hourlyTokensRemaining);

        $this->assertEquals(0, $status->concurrentRequestsActive);
        $this->assertEquals(10, $status->concurrentRequestsLimit);

        $this->assertFalse($status->isThrottled);
        $this->assertEquals('optimal', $status->pacingStatus);
    }

    public function test_can_record_usage_and_decrement_remaining_tokens()
    {
        $this->quotaManager->recordUsage(250, '192.168.1.1', 'user_123');

        $this->assertEquals(199750, $this->quotaManager->getRemainingDailyTokens());
        $this->assertEquals(39750, $this->quotaManager->getRemainingHourlyTokens());

        $status = $this->quotaManager->getStatus();
        $this->assertEquals(250, $status->dailyTokensConsumed);
        $this->assertEquals(250, $status->hourlyTokensConsumed);
        $this->assertEquals(1, $status->dailyProjectRequestsConsumed);
    }

    public function test_concurrency_slots_are_strictly_capped_at_ten()
    {
        // Acquire 10 slots
        for ($i = 0; $i < 10; $i++) {
            $this->assertTrue(
                $this->quotaManager->acquireConcurrencySlot(50),
                "Failed to acquire slot {$i}"
            );
        }

        // 11th slot must be rejected when timeout expires
        $this->assertFalse($this->quotaManager->acquireConcurrencySlot(50));

        // Release 1 slot
        $this->quotaManager->releaseConcurrencySlot();

        // Now can acquire again
        $this->assertTrue($this->quotaManager->acquireConcurrencySlot(50));
    }

    public function test_can_sync_directly_from_google_property_quota_response()
    {
        $dailyQuota = new QuotaStatus(['consumed' => 15000, 'remaining' => 185000]);
        $hourlyQuota = new QuotaStatus(['consumed' => 3200, 'remaining' => 36800]);

        $propertyQuota = new PropertyQuota([
            'tokens_per_day' => $dailyQuota,
            'tokens_per_hour' => $hourlyQuota,
        ]);

        $this->quotaManager->syncFromPropertyQuota($propertyQuota);

        $this->assertEquals(185000, $this->quotaManager->getRemainingDailyTokens());
        $this->assertEquals(36800, $this->quotaManager->getRemainingHourlyTokens());
    }

    public function test_seconds_until_pst_midnight_reset_is_positive()
    {
        $seconds = $this->quotaManager->getSecondsUntilDailyReset();
        $this->assertGreaterThan(0, $seconds);
        $this->assertLessThanOrEqual(86400, $seconds);
    }

    public function test_should_use_stale_cache_when_approaching_warning_threshold()
    {
        $this->assertFalse($this->quotaManager->shouldUseStaleCache());

        // Burn 175,000 tokens (more than 85% of 200,000)
        $this->quotaManager->recordUsage(175000);

        $this->assertTrue($this->quotaManager->shouldUseStaleCache());
    }

    public function test_maximum_quota_utilization_mode_allows_bursting_without_premature_throttling()
    {
        $this->quotaManager->enableMaximumUtilization();
        $this->assertTrue($this->quotaManager->isMaximumUtilization());
        $this->assertEquals('maximum_utilization', $this->quotaManager->getMode());

        // Burn 32,000 tokens (80% of hourly 40,000 cap).
        // In Balanced Mode, this triggers warning/pacing, but in Maximum Utilization Mode, queries continue freely!
        $this->quotaManager->recordUsage(32000);
        $this->assertFalse($this->quotaManager->shouldUseStaleCache());

        // Status reflects maximum utilization mode
        $status = $this->quotaManager->getStatus();
        $this->assertEquals('maximum_utilization', $status->mode);
        $this->assertTrue($status->maxUtilizationEnabled);
    }

    public function test_validates_configuration_and_rejects_illegal_limits()
    {
        $this->expectException(\zfhassaan\genlytics\Exceptions\QuotaValidationException::class);

        // Google GA4 hard limit is 10 concurrent requests; setting 25 must fail validation
        new QuotaManager(Cache::store('array'), [
            'concurrent_requests_limit' => 25,
        ]);
    }

    public function test_validates_execution_rejects_query_when_tokens_exceeded()
    {
        $this->expectException(\zfhassaan\genlytics\Exceptions\QuotaExceededException::class);

        // Asking for 250,000 tokens when limit is 200,000 must throw QuotaExceededException
        $this->quotaManager->validateExecution(250000);
    }

    public function test_estimates_token_costs_accurately()
    {
        $simpleCost = $this->quotaManager->estimateTokens(['today'], [['name' => 'country']], [['name' => 'activeUsers']]);
        $this->assertEquals(1, $simpleCost);

        $complexCost = $this->quotaManager->estimateTokens(
            ['30daysAgo'],
            [['name' => 'country'], ['name' => 'city'], ['name' => 'browser'], ['name' => 'pagePath'], ['name' => 'deviceCategory'], ['name' => 'date']],
            [['name' => 'activeUsers'], ['name' => 'sessions'], ['name' => 'screenPageViews'], ['name' => 'conversions'], ['name' => 'eventCount'], ['name' => 'bounceRate']]
        );
        $this->assertEquals(3, $complexCost);
    }

    public function test_consume_all_daily_quota_mode_and_methods()
    {
        $this->quotaManager->enableAllQuotaConsumption();
        $this->assertEquals('consume_all', $this->quotaManager->getMode());
        $this->assertTrue($this->quotaManager->shouldConsumeAllDailyQuota());

        // Even with high usage, stale cache is NEVER forced while tokens remain
        $this->quotaManager->recordUsage(35000);
        $this->assertFalse($this->quotaManager->shouldUseStaleCache());

        $status = $this->quotaManager->getStatus();
        $this->assertEquals('consume_all', $status->mode);
        $this->assertTrue($status->consumeAllEnabled);
        $this->assertEquals('consume_all_active', $status->pacingStatus);

        $this->quotaManager->setMode('balanced');
        $this->quotaManager->setConsumeAllDailyQuota(false);
        $this->assertFalse($this->quotaManager->shouldConsumeAllDailyQuota());
        $this->quotaManager->setConsumeAllDailyQuota(true);
        $this->assertTrue($this->quotaManager->shouldConsumeAllDailyQuota());
    }

    public function test_drain_daily_quota_executes_until_target()
    {
        $report = $this->quotaManager->drainDailyQuota(function () {
            return 50; // returns 50 tokens per run
        }, targetTokens: 150);

        $this->assertEquals(150, $report['target_tokens']);
        $this->assertEquals(150, $report['total_consumed']);
        $this->assertEquals(3, $report['operations_executed']);
    }

    public function test_quota_usage_updated_event_broadcast_data()
    {
        $status = $this->quotaManager->getStatus();
        $event = new \zfhassaan\genlytics\Events\QuotaUsageUpdated($status, 15);

        $channels = $event->broadcastOn();
        $this->assertCount(2, $channels);
        $this->assertEquals('genlytics-quota', $channels[0]->name);
        $this->assertEquals('quota.updated', $event->broadcastAs());

        $payload = $event->broadcastWith();
        $this->assertEquals(15, $payload['tokens_consumed_this_event']);
        $this->assertEquals($status->dailyTokensLimit, $payload['daily_tokens_limit']);
    }

    public function test_can_execute_checks_ip_and_user_limits()
    {
        $this->assertTrue($this->quotaManager->canExecute('192.168.1.1', 'user-123'));

        // Test validateExecution with IP and User
        $this->assertTrue($this->quotaManager->validateExecution(1, '192.168.1.1', 'user-123'));
    }

    public function test_validate_configuration_rejects_various_invalid_parameters()
    {
        $cases = [
            ['property_type' => 'invalid_prop'],
            ['mode' => 'invalid_mode'],
            ['daily_token_limit' => -5],
            ['hourly_token_limit' => 50000],
            ['concurrent_requests_limit' => 0],
            ['project_daily_request_limit' => 99999],
            ['ip_qps_limit' => 20],
            ['user_requests_per_100s' => 5000],
            ['warning_threshold' => 1.5],
        ];

        foreach ($cases as $invalidConfig) {
            try {
                new QuotaManager(Cache::store('array'), $invalidConfig);
                $this->fail('Expected QuotaValidationException for ' . json_encode($invalidConfig));
            } catch (\zfhassaan\genlytics\Exceptions\QuotaValidationException $e) {
                $this->assertNotEmpty($e->getField());
            }
        }
    }

    public function test_pacing_statuses_critical_throttle_and_reset()
    {
        $this->quotaManager->recordUsage(198000); // 99% of 200,000
        $status = $this->quotaManager->getStatus();
        $this->assertEquals('critical_throttle', $status->pacingStatus);
        $this->assertTrue($status->isThrottled);

        $this->quotaManager->reset();
        $this->assertEquals(200000, $this->quotaManager->getRemainingDailyTokens());
        $this->assertEquals(40000, $this->quotaManager->getRemainingHourlyTokens());
    }

    public function test_drain_daily_quota_handles_exceptions_gracefully()
    {
        $report = $this->quotaManager->drainDailyQuota(function () {
            throw new \RuntimeException('Network timeout during drain');
        }, targetTokens: 100);

        $this->assertCount(1, $report['errors']);
        $this->assertEquals('Network timeout during drain', $report['errors'][0]);
    }
}

