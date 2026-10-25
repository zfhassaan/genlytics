<?php

namespace zfhassaan\genlytics\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Strongly-Typed DTO for Top Page Reports
 */
class PageReportDTO implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $path,
        public readonly string $title,
        public readonly int $views,
        public readonly int $activeUsers,
        public readonly float $averageDuration = 0.0
    ) {
    }

    public static function fromRow(array $row): self
    {
        $dims = $row['dimensions'] ?? [];
        $mets = $row['metrics'] ?? [];

        return new self(
            path: (string) ($dims['pagePath'] ?? $dims['unifiedPagePathScreen'] ?? ''),
            title: (string) ($dims['pageTitle'] ?? ''),
            views: (int) ($mets['screenPageViews'] ?? 0),
            activeUsers: (int) ($mets['activeUsers'] ?? 0),
            averageDuration: (float) ($mets['averageSessionDuration'] ?? $mets['userEngagementDuration'] ?? 0.0)
        );
    }

    public function toArray(): array
    {
        return [
            'path'             => $this->path,
            'title'            => $this->title,
            'views'            => $this->views,
            'active_users'     => $this->activeUsers,
            'average_duration' => $this->averageDuration,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
