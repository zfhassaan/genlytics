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
        public int $step,
        public string $name,
        public string $eventName,
        public int $count,
        public int $dropOffCount,
        public float $dropOffRate,
        public float $conversionRate,
        public float $overallConversionRate
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
