<?php

namespace zfhassaan\genlytics\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Strongly-Typed DTO for Device Category Reports
 */
class DeviceCategoryDTO implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $category,
        public int $activeUsers,
        public int $sessions
    ) {
    }

    public static function fromRow(array $row): self
    {
        $dims = $row['dimensions'] ?? [];
        $mets = $row['metrics'] ?? [];

        return new self(
            category: (string) ($dims['deviceCategory'] ?? 'desktop'),
            activeUsers: (int) ($mets['activeUsers'] ?? 0),
            sessions: (int) ($mets['sessions'] ?? 0)
        );
    }

    public function toArray(): array
    {
        return [
            'category'     => $this->category,
            'active_users' => $this->activeUsers,
            'sessions'     => $this->sessions,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
