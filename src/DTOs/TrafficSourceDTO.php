<?php

namespace zfhassaan\genlytics\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Strongly-Typed DTO for Traffic Acquisition Channels
 */
class TrafficSourceDTO implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $source,
        public readonly string $medium,
        public readonly int $sessions,
        public readonly int $activeUsers,
        public readonly float $bounceRate = 0.0
    ) {
    }

    public static function fromRow(array $row): self
    {
        $dims = $row['dimensions'] ?? [];
        $mets = $row['metrics'] ?? [];

        return new self(
            source: (string) ($dims['sessionSource'] ?? $dims['source'] ?? '(direct)'),
            medium: (string) ($dims['sessionMedium'] ?? $dims['medium'] ?? '(none)'),
            sessions: (int) ($mets['sessions'] ?? 0),
            activeUsers: (int) ($mets['activeUsers'] ?? 0),
            bounceRate: (float) ($mets['bounceRate'] ?? 0.0)
        );
    }

    public function toArray(): array
    {
        return [
            'source'       => $this->source,
            'medium'       => $this->medium,
            'sessions'     => $this->sessions,
            'active_users' => $this->activeUsers,
            'bounce_rate'  => $this->bounceRate,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
