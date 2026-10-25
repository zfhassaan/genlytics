<?php

namespace zfhassaan\genlytics\DTOs;

class QuotaStatusDTO
{
    public string $propertyType;
    public int $dailyTokensLimit;
    public int $dailyTokensConsumed;
    public int $dailyTokensRemaining;
    public float $dailyUtilizationPercent;

    public int $hourlyTokensLimit;
    public int $hourlyTokensConsumed;
    public int $hourlyTokensRemaining;
    public float $hourlyUtilizationPercent;

    public int $concurrentRequestsActive;
    public int $concurrentRequestsLimit;

    public int $dailyProjectRequestsConsumed;
    public int $dailyProjectRequestsLimit;
    public float $dailyProjectUtilizationPercent;

    public int $secondsUntilDailyReset;
    public string $dailyResetAtPst;
    public bool $isThrottled;
    public string $pacingStatus;
    public string $mode;
    public bool $maxUtilizationEnabled;
    public bool $consumeAllEnabled;

    public function __construct(array $data)
    {
        $this->propertyType = (string) ($data['property_type'] ?? 'standard');
        $this->mode = (string) ($data['mode'] ?? 'balanced');
        $this->maxUtilizationEnabled = ($this->mode === 'maximum_utilization');
        $this->consumeAllEnabled = (bool) ($data['consume_all_enabled'] ?? false) || ($this->mode === 'consume_all');
        $this->dailyTokensLimit = (int) ($data['daily_tokens_limit'] ?? 200000);
        $this->dailyTokensConsumed = (int) ($data['daily_tokens_consumed'] ?? 0);
        $this->dailyTokensRemaining = max(0, $this->dailyTokensLimit - $this->dailyTokensConsumed);
        $this->dailyUtilizationPercent = $this->dailyTokensLimit > 0
            ? round(($this->dailyTokensConsumed / $this->dailyTokensLimit) * 100, 2)
            : 0.0;

        $this->hourlyTokensLimit = (int) ($data['hourly_tokens_limit'] ?? 40000);
        $this->hourlyTokensConsumed = (int) ($data['hourly_tokens_consumed'] ?? 0);
        $this->hourlyTokensRemaining = max(0, $this->hourlyTokensLimit - $this->hourlyTokensConsumed);
        $this->hourlyUtilizationPercent = $this->hourlyTokensLimit > 0
            ? round(($this->hourlyTokensConsumed / $this->hourlyTokensLimit) * 100, 2)
            : 0.0;

        $this->concurrentRequestsActive = (int) ($data['concurrent_requests_active'] ?? 0);
        $this->concurrentRequestsLimit = (int) ($data['concurrent_requests_limit'] ?? 10);

        $this->dailyProjectRequestsConsumed = (int) ($data['daily_project_requests_consumed'] ?? 0);
        $this->dailyProjectRequestsLimit = (int) ($data['daily_project_requests_limit'] ?? 50000);
        $this->dailyProjectUtilizationPercent = $this->dailyProjectRequestsLimit > 0
            ? round(($this->dailyProjectRequestsConsumed / $this->dailyProjectRequestsLimit) * 100, 2)
            : 0.0;

        $this->secondsUntilDailyReset = (int) ($data['seconds_until_daily_reset'] ?? 0);
        $this->dailyResetAtPst = (string) ($data['daily_reset_at_pst'] ?? '00:00:00 PST');
        $this->isThrottled = (bool) ($data['is_throttled'] ?? false);
        $this->pacingStatus = (string) ($data['pacing_status'] ?? 'optimal');
    }

    public function toArray(): array
    {
        return [
            'property_type' => $this->propertyType,
            'daily_tokens' => [
                'limit' => $this->dailyTokensLimit,
                'consumed' => $this->dailyTokensConsumed,
                'remaining' => $this->dailyTokensRemaining,
                'utilization_percent' => $this->dailyUtilizationPercent,
            ],
            'hourly_tokens' => [
                'limit' => $this->hourlyTokensLimit,
                'consumed' => $this->hourlyTokensConsumed,
                'remaining' => $this->hourlyTokensRemaining,
                'utilization_percent' => $this->hourlyUtilizationPercent,
            ],
            'concurrency' => [
                'active' => $this->concurrentRequestsActive,
                'limit' => $this->concurrentRequestsLimit,
            ],
            'project_requests' => [
                'limit' => $this->dailyProjectRequestsLimit,
                'consumed' => $this->dailyProjectRequestsConsumed,
                'utilization_percent' => $this->dailyProjectUtilizationPercent,
            ],
            'reset_schedule' => [
                'seconds_remaining' => $this->secondsUntilDailyReset,
                'midnight_pst' => $this->dailyResetAtPst,
            ],
            'pacing' => [
                'is_throttled' => $this->isThrottled,
                'status' => $this->pacingStatus,
                'mode' => $this->mode,
                'max_utilization_enabled' => $this->maxUtilizationEnabled,
                'consume_all_enabled' => $this->consumeAllEnabled,
            ],
        ];
    }
}
