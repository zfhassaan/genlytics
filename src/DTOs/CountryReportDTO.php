<?php

namespace zfhassaan\genlytics\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Strongly-Typed DTO for Country Traffic Reports
 */
class CountryReportDTO implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $country,
        public readonly int $activeUsers,
        public readonly int $sessions
    ) {
    }

    public static function fromRow(array $row): self
    {
        $dims = $row['dimensions'] ?? [];
        $mets = $row['metrics'] ?? [];

        return new self(
            country: (string) ($dims['country'] ?? ''),
            activeUsers: (int) ($mets['activeUsers'] ?? 0),
            sessions: (int) ($mets['sessions'] ?? 0)
        );
    }

    public function toArray(): array
    {
        return [
            'country'      => $this->country,
            'active_users' => $this->activeUsers,
            'sessions'     => $this->sessions,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
