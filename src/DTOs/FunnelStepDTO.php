<?php

namespace zfhassaan\genlytics\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Strongly-Typed DTO for Individual Funnel Step Analysis
 */
class FunnelStepDTO implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly int $step,
        public readonly string $name,
        public readonly string $eventName,
        public readonly int $count,
        public readonly int $dropOffCount,
        public readonly float $dropOffRate,
        public readonly float $conversionRate,
        public readonly float $overallConversionRate
    ) {
    }

    public function toArray(): array
    {
        return [
            'step'                    => $this->step,
            'name'                    => $this->name,
            'event_name'              => $this->eventName,
            'count'                   => $this->count,
            'drop_off_count'          => $this->dropOffCount,
            'drop_off_rate'           => $this->dropOffRate,
            'conversion_rate'         => $this->conversionRate,
            'overall_conversion_rate' => $this->overallConversionRate,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

