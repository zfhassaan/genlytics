<?php

namespace zfhassaan\genlytics\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use JsonSerializable;

/**
 * Strongly-Typed DTO for Complete Funnel Pipeline Analysis
 */
class FunnelPipelineDTO implements Arrayable, JsonSerializable
{
    /**
     * @param string $name
     * @param int $totalEntries
     * @param int $totalCompletions
     * @param float $overallConversionRate
     * @param Collection<int, FunnelStepDTO> $steps
     */
    public function __construct(
        public readonly string $name,
        public readonly int $totalEntries,
        public readonly int $totalCompletions,
        public readonly float $overallConversionRate,
        public readonly Collection $steps
    ) {
    }

    public function toArray(): array
    {
        return [
            'name'                    => $this->name,
            'total_entries'           => $this->totalEntries,
            'total_completions'       => $this->totalCompletions,
            'overall_conversion_rate' => $this->overallConversionRate,
            'steps'                   => $this->steps->map(fn ($s) => $s instanceof FunnelStepDTO ? $s->toArray() : (array) $s)->all(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
