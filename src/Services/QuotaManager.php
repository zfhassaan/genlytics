<?php

namespace zfhassaan\genlytics\Services;

use Carbon\Carbon;
use Google\Analytics\Data\V1beta\PropertyQuota;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use zfhassaan\genlytics\Contracts\QuotaManagerInterface;
use zfhassaan\genlytics\DTOs\QuotaStatusDTO;
use zfhassaan\genlytics\Exceptions\QuotaExceededException;
use zfhassaan\genlytics\Exceptions\QuotaValidationException;

class QuotaManager implements QuotaManagerInterface
{
    public const MODE_MAXIMUM_UTILIZATION = 'maximum_utilization';
    public const MODE_BALANCED = 'balanced';
    public const MODE_CONSERVATIVE = 'conservative';
    public const MODE_CONSUME_ALL = 'consume_all';

    protected CacheRepository $cache;
    protected string $prefix;
    protected string $propertyType;
    protected string $mode;
    protected bool $consumeAllDailyQuota = false;
    protected bool $realtimeBroadcasting = true;
    protected string $broadcastChannel = 'genlytics-quota';
    protected int $dailyTokensLimit;
    protected int $hourlyTokensLimit;
    protected int $concurrencyLimit;
    protected int $projectDailyRequestLimit;
    protected int $ipQpsLimit;
    protected int $user100sLimit;
    protected float $warningThreshold;
    protected string $timezone;

    public function __construct(
        CacheRepository $cache,
        array $config = []
    ) {
        $this->cache = $cache;
        $validated = $this->validateConfiguration($config);

        $this->prefix = $validated['prefix'];
        $this->propertyType = $validated['property_type'];
        $this->mode = $validated['mode'];
        $this->consumeAllDailyQuota = (bool) ($validated['consume_all_daily_quota'] ?? false) || ($this->mode === self::MODE_CONSUME_ALL);
        $this->realtimeBroadcasting = (bool) ($validated['realtime_broadcasting'] ?? true);
        $this->broadcastChannel = (string) ($validated['broadcast_channel'] ?? 'genlytics-quota');
        $this->dailyTokensLimit = $validated['daily_token_limit'];
        $this->hourlyTokensLimit = $validated['hourly_token_limit'];
        $this->concurrencyLimit = $validated['concurrent_requests_limit'];
        $this->projectDailyRequestLimit = $validated['project_daily_request_limit'];
        $this->ipQpsLimit = $validated['ip_qps_limit'];
        $this->user100sLimit = $validated['user_requests_per_100s'];
        $this->warningThreshold = $validated['warning_threshold'];
        $this->timezone = $validated['timezone'];

        // If in Consume All Quota Mode or Maximum Utilization Mode, push threshold to allow up to 100% consumption
        if ($this->consumeAllDailyQuota || $this->mode === self::MODE_CONSUME_ALL) {
            $this->warningThreshold = 1.0;
        } elseif ($this->mode === self::MODE_MAXIMUM_UTILIZATION) {
            $this->warningThreshold = max($this->warningThreshold, 0.99);
        }
    }

    /**
     * Validate quota configuration parameters against Google API limits.
     *
     * @param array $config
     * @return array
     * @throws QuotaValidationException
     */
    public function validateConfiguration(array $config): array
    {
        $prefix = (string) ($config['prefix'] ?? 'genlytics:quota:');
        $propertyType = (string) ($config['property_type'] ?? 'standard');
        if (!in_array($propertyType, ['standard', '360'], true)) {
            throw new QuotaValidationException('property_type', $propertyType, ['standard', '360']);
        }

        $mode = (string) ($config['mode'] ?? self::MODE_BALANCED);
        if (!in_array($mode, [self::MODE_MAXIMUM_UTILIZATION, self::MODE_BALANCED, self::MODE_CONSERVATIVE, self::MODE_CONSUME_ALL], true)) {
            throw new QuotaValidationException('mode', $mode, [
                self::MODE_MAXIMUM_UTILIZATION,
                self::MODE_BALANCED,
                self::MODE_CONSERVATIVE,
                self::MODE_CONSUME_ALL,
            ]);
        }

        // Standard: default 200,000 | 360: default 2,000,000
        $defaultDaily = $propertyType === '360' ? 2000000 : 200000;
        $dailyTokensLimit = (int) ($config['daily_token_limit'] ?? $defaultDaily);
        if ($dailyTokensLimit <= 0) {
            throw new QuotaValidationException('daily_token_limit', $dailyTokensLimit, '> 0');
        }

        // Hourly core token hard cap is 40,000
        $hourlyTokensLimit = (int) ($config['hourly_token_limit'] ?? 40000);
        if ($hourlyTokensLimit <= 0 || $hourlyTokensLimit > 40000) {
            throw new QuotaValidationException('hourly_token_limit', $hourlyTokensLimit, '1 to 40,000');
        }

        // Concurrency hard cap is 10 concurrent requests
        $concurrencyLimit = (int) ($config['concurrent_requests_limit'] ?? 10);
        if ($concurrencyLimit < 1 || $concurrencyLimit > 10) {
            throw new QuotaValidationException('concurrent_requests_limit', $concurrencyLimit, '1 to 10');
        }

        // Project daily request cap is 50,000 requests/day
        $projectDailyRequestLimit = (int) ($config['project_daily_request_limit'] ?? 50000);
        if ($projectDailyRequestLimit <= 0 || $projectDailyRequestLimit > 50000) {
            throw new QuotaValidationException('project_daily_request_limit', $projectDailyRequestLimit, '1 to 50,000');
        }

        // IP QPS limit is 10 queries/second
        $ipQpsLimit = (int) ($config['ip_qps_limit'] ?? 10);
        if ($ipQpsLimit < 1 || $ipQpsLimit > 10) {
            throw new QuotaValidationException('ip_qps_limit', $ipQpsLimit, '1 to 10');
        }

        // User quota: default 100, adjustable up to 1,000
        $user100sLimit = (int) ($config['user_requests_per_100s'] ?? 100);
        if ($user100sLimit < 1 || $user100sLimit > 1000) {
            throw new QuotaValidationException('user_requests_per_100s', $user100sLimit, '1 to 1,000');
        }

        $warningThreshold = (float) ($config['warning_threshold'] ?? ($mode === self::MODE_MAXIMUM_UTILIZATION ? 0.99 : 0.85));
        if ($warningThreshold <= 0.0 || $warningThreshold > 1.0) {
            throw new QuotaValidationException('warning_threshold', $warningThreshold, '0.01 to 1.0');
        }

        $timezone = (string) ($config['timezone'] ?? 'America/Los_Angeles');
        $consumeAllDailyQuota = (bool) ($config['consume_all_daily_quota'] ?? false);
        $realtimeBroadcasting = (bool) ($config['realtime_broadcasting'] ?? true);
        $broadcastChannel = (string) ($config['broadcast_channel'] ?? 'genlytics-quota');

        return [
            'prefix' => $prefix,
            'property_type' => $propertyType,
            'mode' => $mode,
            'consume_all_daily_quota' => $consumeAllDailyQuota,
            'realtime_broadcasting' => $realtimeBroadcasting,
            'broadcast_channel' => $broadcastChannel,
            'daily_token_limit' => $dailyTokensLimit,
            'hourly_token_limit' => $hourlyTokensLimit,
            'concurrent_requests_limit' => $concurrencyLimit,
            'project_daily_request_limit' => $projectDailyRequestLimit,
            'ip_qps_limit' => $ipQpsLimit,
            'user_requests_per_100s' => $user100sLimit,
            'warning_threshold' => $warningThreshold,
            'timezone' => $timezone,
        ];
    }

    /**
     * Set operational mode ('maximum_utilization', 'balanced', 'conservative').
     *
     * @param string $mode
     * @return self
     */
    public function setMode(string $mode): self
    {
        if (!in_array($mode, [
            self::MODE_MAXIMUM_UTILIZATION,
            self::MODE_BALANCED,
            self::MODE_CONSERVATIVE,
            self::MODE_CONSUME_ALL,
        ], true)) {
            throw new QuotaValidationException('mode', $mode, [
                self::MODE_MAXIMUM_UTILIZATION,
                self::MODE_BALANCED,
                self::MODE_CONSERVATIVE,
                self::MODE_CONSUME_ALL,
            ]);
        }

        $this->mode = $mode;
        $this->warningThreshold = match ($mode) {
            self::MODE_CONSUME_ALL => 1.0,
            self::MODE_MAXIMUM_UTILIZATION => 0.99,
            self::MODE_CONSERVATIVE => 0.70,
            default => 0.85,
        };

        if ($mode === self::MODE_CONSUME_ALL) {
            $this->consumeAllDailyQuota = true;
        }

        return $this;
    }

    /**
     * Get the current operational mode.
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Enable Maximum Quota Utilization Mode.
     * Unlocks up to 100% token usage, relaxes hourly pacing, and maximizes concurrency.
     */
    public function enableMaximumUtilization(): self
    {
        return $this->setMode(self::MODE_MAXIMUM_UTILIZATION);
    }

    /**
     * Check if maximum quota utilization mode is currently active.
     */
    public function isMaximumUtilization(): bool
    {
        return $this->mode === self::MODE_MAXIMUM_UTILIZATION;
    }

    /**
     * Enable Full Quota Consumption Mode (unlocks 100% of daily tokens).
     */
    public function enableAllQuotaConsumption(): self
    {
        return $this->setMode(self::MODE_CONSUME_ALL);
    }

    /**
     * Check if 100% daily quota consumption is active.
     */
    public function shouldConsumeAllDailyQuota(): bool
    {
        return $this->consumeAllDailyQuota || ($this->mode === self::MODE_CONSUME_ALL);
    }

    /**
     * Set whether to consume 100% of daily quota.
     */
    public function setConsumeAllDailyQuota(bool $enable = true): self
    {
        $this->consumeAllDailyQuota = $enable;
        if ($enable) {
            $this->warningThreshold = 1.0;
        }

        return $this;
    }

    /**
     * Drain remaining daily quota through pre-warming execution jobs.
     *
     * @param callable $executor Callback returning tokens consumed per query
     * @param int $targetTokens Number of tokens to consume (-1 for all remaining)
     * @return array Execution report
     */
    public function drainDailyQuota(callable $executor, int $targetTokens = -1): array
    {
        $this->enableAllQuotaConsumption();
        $initialDailyRemaining = $this->getRemainingDailyTokens();

        $tokensToConsume = ($targetTokens > 0)
            ? min($targetTokens, $initialDailyRemaining)
            : $initialDailyRemaining;

        $totalConsumed = 0;
        $operationsExecuted = 0;
        $errors = [];

        while ($totalConsumed < $tokensToConsume && $this->getRemainingDailyTokens() > 0 && $this->getRemainingHourlyTokens() > 0) {
            if (!$this->acquireConcurrencySlot(1000)) {
                usleep(50000);
                continue;
            }

            try {
                $consumedThisOp = (int) $executor();
                $consumedThisOp = max(1, $consumedThisOp);
                $this->recordUsage($consumedThisOp);
                $totalConsumed += $consumedThisOp;
                $operationsExecuted++;
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
                break;
            } finally {
                $this->releaseConcurrencySlot();
            }

            usleep(20000); // 20ms pause between queries to respect IP limit
        }

        return [
            'initial_remaining' => $initialDailyRemaining,
            'target_tokens' => $tokensToConsume,
            'total_consumed' => $totalConsumed,
            'final_remaining' => $this->getRemainingDailyTokens(),
            'operations_executed' => $operationsExecuted,
            'errors' => $errors,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function canExecute(?string $ip = null, ?string $userId = null): bool
    {
        // 1. Check daily tokens
        if ($this->getRemainingDailyTokens() <= 0) {
            return false;
        }

        // 2. Check hourly tokens
        if ($this->getRemainingHourlyTokens() <= 0) {
            return false;
        }

        // 3. Check daily project requests
        if ($this->getDailyProjectRequestsRemaining() <= 0) {
            return false;
        }

        // 4. Check IP QPS limit (10 QPS)
        if ($ip !== null && !$this->checkIpRateLimit($ip)) {
            return false;
        }

        // 5. Check User rate limit (100 req / 100s, adjustable up to 1,000)
        if ($userId !== null && !$this->checkUserRateLimit($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Validate a query execution request prior to hitting Google Analytics API.
     *
     * @param int $estimatedTokens
     * @param string|null $ip
     * @param string|null $userId
     * @return bool
     * @throws QuotaExceededException
     */
    public function validateExecution(int $estimatedTokens = 1, ?string $ip = null, ?string $userId = null): bool
    {
        $estimatedTokens = max(1, $estimatedTokens);

        // Daily token budget check
        $dailyRemaining = $this->getRemainingDailyTokens();
        if ($estimatedTokens > $dailyRemaining) {
            throw new QuotaExceededException(
                'daily_core_tokens',
                $this->dailyTokensLimit,
                $this->dailyTokensLimit - $dailyRemaining,
                $this->getSecondsUntilDailyReset(),
                sprintf(
                    'Query requires %d tokens, but only %d core tokens remain for today. Daily reset occurs at midnight PST.',
                    $estimatedTokens,
                    $dailyRemaining
                )
            );
        }

        // Hourly token budget check
        $hourlyRemaining = $this->getRemainingHourlyTokens();
        if ($estimatedTokens > $hourlyRemaining) {
            throw new QuotaExceededException(
                'hourly_core_tokens',
                $this->hourlyTokensLimit,
                $this->hourlyTokensLimit - $hourlyRemaining,
                $this->getSecondsUntilHourlyReset(),
                sprintf(
                    'Query requires %d tokens, but only %d hourly tokens remain for this hour.',
                    $estimatedTokens,
                    $hourlyRemaining
                )
            );
        }

        // IP Rate Limit validation
        if ($ip !== null && !$this->checkIpRateLimit($ip)) {
            throw new QuotaExceededException(
                'ip_qps_limit',
                $this->ipQpsLimit,
                $this->ipQpsLimit,
                1,
                sprintf('IP %s exceeded the limit of %d queries per second.', $ip, $this->ipQpsLimit)
            );
        }

        // User Rate Limit validation
        if ($userId !== null && !$this->checkUserRateLimit($userId)) {
            throw new QuotaExceededException(
                'user_rate_limit',
                $this->user100sLimit,
                $this->user100sLimit,
                100,
                sprintf('User %s exceeded the limit of %d requests per 100 seconds.', $userId, $this->user100sLimit)
            );
        }

        return true;
    }

    /**
     * Estimate token cost for a query based on GA4 dimensions and metrics.
     * Google Analytics Data API charges 1 base token per query + 1 token per 10,000 rows.
     */
    public function estimateTokens(array $dateRange, array $dimensions, array $metrics): int
    {
        $base = 1;
        $dimCount = count($dimensions);
        $metCount = count($metrics);

        // Complex queries with multiple dimensions and metrics consume proportionally more tokens
        if ($dimCount > 5 || $metCount > 5) {
            $base += 2;
        }

        return max(1, $base);
    }

    /**
     * {@inheritDoc}
     */
    public function acquireConcurrencySlot(int $timeoutMs = 500): bool
    {
        $key = $this->prefix . 'concurrency';
        $startTime = microtime(true) * 1000;

        do {
            // Atomic check and increment
            $current = (int) $this->cache->get($key, 0);
            if ($current < $this->concurrencyLimit) {
                $newVal = $this->cache->increment($key);
                if ($newVal <= $this->concurrencyLimit) {
                    return true;
                }
                // Over limit from race condition, decrement back
                $this->cache->decrement($key);
            }

            // Micro-sleep between 20ms and 50ms with jitter
            usleep(random_int(20000, 50000));
        } while ((microtime(true) * 1000) - $startTime < $timeoutMs);

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function releaseConcurrencySlot(): void
    {
        $key = $this->prefix . 'concurrency';
        $current = (int) $this->cache->get($key, 0);
        if ($current > 0) {
            $this->cache->decrement($key);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function recordUsage(int $tokens = 1, ?string $ip = null, ?string $userId = null): void
    {
        $tokens = max(1, $tokens);

        // Daily tokens increment
        $dailyKey = $this->getDailyKey();
        $this->cache->increment($dailyKey, $tokens);
        if ($this->cache->get($dailyKey . ':ttl_set') === null) {
            $this->cache->put($dailyKey . ':ttl_set', 1, $this->getSecondsUntilDailyReset());
        }

        // Hourly tokens increment
        $hourlyKey = $this->getHourlyKey();
        $this->cache->increment($hourlyKey, $tokens);

        // Project daily requests increment
        $projectKey = $this->getProjectDailyKey();
        $this->cache->increment($projectKey, 1);

        // IP Rate Limit tracking (10 req/s window)
        if ($ip !== null) {
            $ipKey = $this->prefix . 'ip:' . md5($ip) . ':' . time();
            $this->cache->increment($ipKey, 1);
            $this->cache->put($ipKey . ':ttl', 1, 2);
        }

        // User Rate Limit tracking (100-1000 req/100s window)
        if ($userId !== null) {
            $window = (int) floor(time() / 100);
            $userKey = $this->prefix . 'user:' . md5($userId) . ':' . $window;
            $this->cache->increment($userKey, 1);
            $this->cache->put($userKey . ':ttl', 1, 110);
        }

        // Fire real-time broadcast event if enabled
        if ($this->realtimeBroadcasting) {
            try {
                if (class_exists(\zfhassaan\genlytics\Events\QuotaUsageUpdated::class)) {
                    event(new \zfhassaan\genlytics\Events\QuotaUsageUpdated(
                        $this->getStatus(),
                        $tokens,
                        $this->broadcastChannel
                    ));
                }
            } catch (\Throwable) {
                // Non-blocking in environments without broadcast driver
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function syncFromPropertyQuota(PropertyQuota $propertyQuota): void
    {
        // Google server-side ground truth synchronization
        $tokensPerDay = $propertyQuota->getTokensPerDay();
        if ($tokensPerDay !== null) {
            $consumed = $tokensPerDay->getConsumed();
            $this->cache->put($this->getDailyKey(), $consumed, $this->getSecondsUntilDailyReset());
        }

        $tokensPerHour = $propertyQuota->getTokensPerHour();
        if ($tokensPerHour !== null) {
            $consumed = $tokensPerHour->getConsumed();
            $this->cache->put($this->getHourlyKey(), $consumed, $this->getSecondsUntilHourlyReset());
        }

        // Fire real-time broadcast event if enabled
        if ($this->realtimeBroadcasting) {
            try {
                if (class_exists(\zfhassaan\genlytics\Events\QuotaUsageUpdated::class)) {
                    event(new \zfhassaan\genlytics\Events\QuotaUsageUpdated(
                        $this->getStatus(),
                        0,
                        $this->broadcastChannel
                    ));
                }
            } catch (\Throwable) {
                // Non-blocking
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus(): QuotaStatusDTO
    {
        $dailyConsumed = (int) $this->cache->get($this->getDailyKey(), 0);
        $hourlyConsumed = (int) $this->cache->get($this->getHourlyKey(), 0);
        $concurrentActive = max(0, (int) $this->cache->get($this->prefix . 'concurrency', 0));
        $projectDailyConsumed = (int) $this->cache->get($this->getProjectDailyKey(), 0);

        $nowPst = Carbon::now($this->timezone);
        $dailyResetAtPst = $nowPst->copy()->endOfDay()->format('H:i:s T');
        $secondsRemaining = $this->getSecondsUntilDailyReset();

        // Calculate dynamic pacing status
        $pacingStatus = $this->calculatePacingStatus($dailyConsumed, $hourlyConsumed);
        $isThrottled = $pacingStatus === 'critical_throttle' || $dailyConsumed >= $this->dailyTokensLimit;

        return new QuotaStatusDTO([
            'property_type' => $this->propertyType,
            'mode' => $this->mode,
            'consume_all_enabled' => ($this->consumeAllDailyQuota || $this->mode === self::MODE_CONSUME_ALL),
            'daily_tokens_limit' => $this->dailyTokensLimit,
            'daily_tokens_consumed' => $dailyConsumed,
            'hourly_tokens_limit' => $this->hourlyTokensLimit,
            'hourly_tokens_consumed' => $hourlyConsumed,
            'concurrent_requests_active' => $concurrentActive,
            'concurrent_requests_limit' => $this->concurrencyLimit,
            'daily_project_requests_consumed' => $projectDailyConsumed,
            'daily_project_requests_limit' => $this->projectDailyRequestLimit,
            'seconds_until_daily_reset' => $secondsRemaining,
            'daily_reset_at_pst' => $dailyResetAtPst,
            'is_throttled' => $isThrottled,
            'pacing_status' => $pacingStatus,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getRemainingDailyTokens(): int
    {
        $consumed = (int) $this->cache->get($this->getDailyKey(), 0);
        return max(0, $this->dailyTokensLimit - $consumed);
    }

    /**
     * {@inheritDoc}
     */
    public function getRemainingHourlyTokens(): int
    {
        $consumed = (int) $this->cache->get($this->getHourlyKey(), 0);
        return max(0, $this->hourlyTokensLimit - $consumed);
    }

    public function getDailyProjectRequestsRemaining(): int
    {
        $consumed = (int) $this->cache->get($this->getProjectDailyKey(), 0);
        return max(0, $this->projectDailyRequestLimit - $consumed);
    }

    /**
     * {@inheritDoc}
     */
    public function getSecondsUntilDailyReset(): int
    {
        $nowPst = Carbon::now($this->timezone);
        $endOfDayPst = $nowPst->copy()->endOfDay();
        return max(1, $nowPst->diffInSeconds($endOfDayPst));
    }

    public function getSecondsUntilHourlyReset(): int
    {
        $now = Carbon::now();
        $endOfHour = $now->copy()->endOfHour();
        return max(1, $now->diffInSeconds($endOfHour));
    }

    /**
     * {@inheritDoc}
     */
    public function shouldUseStaleCache(): bool
    {
        $dailyRemaining = $this->getRemainingDailyTokens();
        $hourlyRemaining = $this->getRemainingHourlyTokens();

        // When user wants to consume all quota per day:
        // NEVER divert queries to stale cache while tokens remain!
        if ($this->consumeAllDailyQuota || $this->mode === self::MODE_CONSUME_ALL) {
            return ($dailyRemaining <= 0) || ($hourlyRemaining <= 0);
        }

        // In Maximum Utilization Mode:
        // Do NOT divert queries to stale cache unless tokens are genuinely exhausted (remaining <= 1%)
        if ($this->mode === self::MODE_MAXIMUM_UTILIZATION) {
            return ($dailyRemaining < ($this->dailyTokensLimit * 0.01)) || ($hourlyRemaining < ($this->hourlyTokensLimit * 0.01));
        }

        // In Balanced or Conservative Mode:
        // Guard token budget early (85% or 70% threshold)
        if ($dailyRemaining < ($this->dailyTokensLimit * (1 - $this->warningThreshold))) {
            return true;
        }

        if ($hourlyRemaining < ($this->hourlyTokensLimit * 0.10)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->cache->forget($this->getDailyKey());
        $this->cache->forget($this->getHourlyKey());
        $this->cache->forget($this->getProjectDailyKey());
        $this->cache->forget($this->prefix . 'concurrency');
    }

    protected function checkIpRateLimit(string $ip): bool
    {
        $ipKey = $this->prefix . 'ip:' . md5($ip) . ':' . time();
        $current = (int) $this->cache->get($ipKey, 0);
        return $current < $this->ipQpsLimit;
    }

    protected function checkUserRateLimit(string $userId): bool
    {
        $window = (int) floor(time() / 100);
        $userKey = $this->prefix . 'user:' . md5($userId) . ':' . $window;
        $current = (int) $this->cache->get($userKey, 0);
        return $current < $this->user100sLimit;
    }

    /**
     * Calculate dynamic pacing status to maximize resource utilization per day.
     */
    protected function calculatePacingStatus(int $dailyConsumed, int $hourlyConsumed): string
    {
        // When consuming all quota, run full velocity until daily or hourly limits are hit
        if ($this->consumeAllDailyQuota || $this->mode === self::MODE_CONSUME_ALL) {
            if ($dailyConsumed >= $this->dailyTokensLimit) {
                return 'quota_fully_consumed';
            }
            if ($hourlyConsumed >= $this->hourlyTokensLimit) {
                return 'hourly_cap_reached';
            }
            return 'consume_all_active';
        }

        // In Maximum Utilization Mode, allow bursting up to 99% before throttling
        $throttleRatio = $this->mode === self::MODE_MAXIMUM_UTILIZATION ? 0.99 : 0.95;

        // 1. Check if hourly limit is almost exhausted
        if ($hourlyConsumed >= $this->hourlyTokensLimit * $throttleRatio) {
            return 'critical_throttle';
        }

        // 2. Check if daily limit is almost exhausted
        if ($dailyConsumed >= $this->dailyTokensLimit * $throttleRatio) {
            return 'critical_throttle';
        }

        // In Maximum Utilization Mode, bypass artificial smooth pacing
        if ($this->mode === self::MODE_MAXIMUM_UTILIZATION) {
            return 'maximum_utilization';
        }

        // 3. Compute steady-state hourly target based on hours remaining in PST day
        $nowPst = Carbon::now($this->timezone);
        $hoursRemainingInPstDay = max(1, 24 - $nowPst->hour);
        $dailyTokensRemaining = max(0, $this->dailyTokensLimit - $dailyConsumed);
        $targetHourlyRate = (int) ($dailyTokensRemaining / $hoursRemainingInPstDay);

        // If current hourly consumption exceeds 1.5x sustainable rate in balanced mode
        if ($hourlyConsumed > ($targetHourlyRate * 1.5) && $hourlyConsumed > 10000) {
            return 'pacing_required';
        }

        return 'optimal';
    }

    protected function getDailyKey(): string
    {
        $datePst = Carbon::now($this->timezone)->format('Y-m-d');
        return $this->prefix . 'daily:' . $datePst;
    }

    protected function getHourlyKey(): string
    {
        $hour = Carbon::now()->format('Y-m-d-H');
        return $this->prefix . 'hourly:' . $hour;
    }

    protected function getProjectDailyKey(): string
    {
        $datePst = Carbon::now($this->timezone)->format('Y-m-d');
        return $this->prefix . 'project_daily:' . $datePst;
    }
}
