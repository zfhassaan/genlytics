<?php

namespace zfhassaan\genlytics\Contracts;

use Google\Analytics\Data\V1beta\PropertyQuota;
use zfhassaan\genlytics\DTOs\QuotaStatusDTO;

interface QuotaManagerInterface
{
    /**
     * Operational modes
     */
    public const MODE_MAXIMUM_UTILIZATION = 'maximum_utilization';
    public const MODE_BALANCED = 'balanced';
    public const MODE_CONSERVATIVE = 'conservative';
    public const MODE_CONSUME_ALL = 'consume_all';

    /**
     * Check if a request can be executed within quotas.
     *
     * @param string|null $ip
     * @param string|null $userId
     * @return bool
     */
    public function canExecute(?string $ip = null, ?string $userId = null): bool;

    /**
     * Acquire a concurrency slot (max 10 concurrent requests).
     *
     * @param int $timeoutMs Max milliseconds to wait for a slot.
     * @return bool True if slot acquired, false otherwise.
     */
    public function acquireConcurrencySlot(int $timeoutMs = 500): bool;

    /**
     * Release an acquired concurrency slot.
     *
     * @return void
     */
    public function releaseConcurrencySlot(): void;

    /**
     * Record consumed tokens and request counts.
     *
     * @param int $tokens
     * @param string|null $ip
     * @param string|null $userId
     * @return void
     */
    public function recordUsage(int $tokens = 1, ?string $ip = null, ?string $userId = null): void;

    /**
     * Synchronize actual quota status from Google's PropertyQuota object.
     *
     * @param PropertyQuota $propertyQuota
     * @return void
     */
    public function syncFromPropertyQuota(PropertyQuota $propertyQuota): void;

    /**
     * Get the full quota and pacing status DTO.
     *
     * @return QuotaStatusDTO
     */
    public function getStatus(): QuotaStatusDTO;

    /**
     * Get remaining daily core tokens.
     *
     * @return int
     */
    public function getRemainingDailyTokens(): int;

    /**
     * Get remaining hourly core tokens.
     *
     * @return int
     */
    public function getRemainingHourlyTokens(): int;

    /**
     * Get seconds remaining until midnight Pacific Standard Time (PST).
     *
     * @return int
     */
    public function getSecondsUntilDailyReset(): int;

    /**
     * Determine whether queries should fall back to cached data to maximize quota budget.
     *
     * @return bool
     */
    public function shouldUseStaleCache(): bool;

    /**
     * Set operational mode ('maximum_utilization', 'balanced', 'conservative').
     *
     * @param string $mode
     * @return self
     */
    public function setMode(string $mode): self;

    /**
     * Get the current operational mode.
     *
     * @return string
     */
    public function getMode(): string;

    /**
     * Enable Maximum Quota Utilization Mode (allows bursting up to 100% of limits).
     *
     * @return self
     */
    public function enableMaximumUtilization(): self;

    /**
     * Check if maximum quota utilization mode is currently active.
     *
     * @return bool
     */
    public function isMaximumUtilization(): bool;

    /**
     * Validate quota configuration parameters against Google API limits.
     *
     * @param array $config
     * @return array Validated configuration
     */
    public function validateConfiguration(array $config): array;

    /**
     * Validate a query execution request prior to hitting Google Analytics API.
     *
     * @param int $estimatedTokens
     * @param string|null $ip
     * @param string|null $userId
     * @return bool
     */
    public function validateExecution(int $estimatedTokens = 1, ?string $ip = null, ?string $userId = null): bool;

    /**
     * Estimate token cost for a query based on dimensions and metrics.
     *
     * @param array $dateRange
     * @param array $dimensions
     * @param array $metrics
     * @return int
     */
    public function estimateTokens(array $dateRange, array $dimensions, array $metrics): int;

    /**
     * Enable Full Quota Consumption Mode (unlocks 100% of daily tokens).
     *
     * @return self
     */
    public function enableAllQuotaConsumption(): self;

    /**
     * Check if 100% daily quota consumption is enabled.
     *
     * @return bool
     */
    public function shouldConsumeAllDailyQuota(): bool;

    /**
     * Set whether to consume 100% of daily quota.
     *
     * @param bool $enable
     * @return self
     */
    public function setConsumeAllDailyQuota(bool $enable = true): self;

    /**
     * Drain remaining daily quota through pre-warming execution jobs.
     *
     * @param callable $executor Callback returning tokens consumed per query
     * @param int $targetTokens Number of tokens to consume (-1 for all remaining)
     * @return array Execution report
     */
    public function drainDailyQuota(callable $executor, int $targetTokens = -1): array;

    /**
     * Reset all internal quota counters (e.g. for testing).
     *
     * @return void
     */
    public function reset(): void;
}
