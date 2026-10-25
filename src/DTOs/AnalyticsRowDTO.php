<?php

namespace zfhassaan\genlytics\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Generic Strongly-Typed Row DTO for GA4 responses
 */
class AnalyticsRowDTO implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $dimensions
     * @param array<string, mixed> $metrics
     */
    public function __construct(
        public array $dimensions = [],
        public array $metrics = []
    ) {
    }

    /**
     * Create DTO from raw transformer row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            dimensions: $row['dimensions'] ?? [],
            metrics: $row['metrics'] ?? []
        );
    }

    /**
     * Get a dimension by key with optional fallback
     */
    public function getDimension(string $name, mixed $default = null): mixed
    {
        return $this->dimensions[$name] ?? $default;
    }

    /**
     * Get a metric by key with type-casting support
     */
    public function getMetric(string $name, mixed $default = 0): mixed
    {
        return $this->metrics[$name] ?? $default;
    }

    /**
     * Get metric as integer
     */
    public function getIntMetric(string $name, int $default = 0): int
    {
        $val = $this->getMetric($name, $default);
        return is_numeric($val) ? (int) $val : $default;
    }

    /**
     * Get metric as float
     */
    public function getFloatMetric(string $name, float $default = 0.0): float
    {
        $val = $this->getMetric($name, $default);
        return is_numeric($val) ? (float) $val : $default;
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'dimensions' => $this->dimensions,
            'metrics'    => $this->metrics,
        ];
    }

    /**
     * Json serialization support
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
