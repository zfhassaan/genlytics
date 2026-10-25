<?php

namespace zfhassaan\genlytics\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use zfhassaan\genlytics\DTOs\QuotaStatusDTO;
use zfhassaan\genlytics\Http\Resources\Concerns\HasGenlyticsVersion;

/**
 * @property QuotaStatusDTO $resource
 */
class QuotaStatusResource extends JsonResource
{
    use HasGenlyticsVersion;
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        /** @var QuotaStatusDTO $dto */
        $dto = $this->resource instanceof QuotaStatusDTO
            ? $this->resource
            : new QuotaStatusDTO((array) $this->resource);

        return [
            'property_type' => $dto->propertyType,
            'daily_tokens' => [
                'limit' => $dto->dailyTokensLimit,
                'consumed' => $dto->dailyTokensConsumed,
                'remaining' => $dto->dailyTokensRemaining,
                'utilization_percent' => $dto->dailyUtilizationPercent,
            ],
            'hourly_tokens' => [
                'limit' => $dto->hourlyTokensLimit,
                'consumed' => $dto->hourlyTokensConsumed,
                'remaining' => $dto->hourlyTokensRemaining,
                'utilization_percent' => $dto->hourlyUtilizationPercent,
            ],
            'concurrency' => [
                'active' => $dto->concurrentRequestsActive,
                'limit' => $dto->concurrentRequestsLimit,
            ],
            'project_requests' => [
                'limit' => $dto->dailyProjectRequestsLimit,
                'consumed' => $dto->dailyProjectRequestsConsumed,
                'utilization_percent' => $dto->dailyProjectUtilizationPercent,
            ],
            'reset_schedule' => [
                'seconds_remaining' => $dto->secondsUntilDailyReset,
                'midnight_pst' => $dto->dailyResetAtPst,
            ],
            'pacing' => [
                'is_throttled' => $dto->isThrottled,
                'status' => $dto->pacingStatus,
                'mode' => $dto->mode,
                'max_utilization_enabled' => $dto->maxUtilizationEnabled,
                'consume_all_enabled' => $dto->consumeAllEnabled,
            ],
        ];
    }
}
