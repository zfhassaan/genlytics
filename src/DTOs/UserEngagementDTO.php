<?php

namespace zfhassaan\genlytics\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Strongly-Typed DTO for Overall User Engagement
 */
class UserEngagementDTO implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly int $activeUsers,
        public readonly int $newUsers,
        public readonly int $engagedSessions,
        public readonly float $engagementRate,
        public readonly float $engagementDuration
    ) {
    }

    public static function fromRow(array $row): self
    {
        $mets = $row['metrics'] ?? [];

        return new self(
            activeUsers: (int) ($mets['activeUsers'] ?? 0),
            newUsers: (int) ($mets['newUsers'] ?? 0),
            engagedSessions: (int) ($mets['engagedSessions'] ?? 0),
            engagementRate: (float) ($mets['engagementRate'] ?? 0.0),
            engagementDuration: (float) ($mets['userEngagementDuration'] ?? 0.0)
        );
    }

    public function toArray(): array
    {
        return [
            'active_users'        => $this->activeUsers,
            'new_users'           => $this->newUsers,
            'engaged_sessions'    => $this->engagedSessions,
            'engagement_rate'     => $this->engagementRate,
            'engagement_duration' => $this->engagementDuration,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

