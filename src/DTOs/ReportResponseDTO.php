<?php

namespace zfhassaan\genlytics\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Standardized Response Wrapper DTO
 */
class ReportResponseDTO implements Arrayable, JsonSerializable
{
    /**
     * @param array $data Transformed records or DTOs
     * @param string $reportType Type of report (report, realtime, dimension, batch)
     * @param bool $fromCache Whether data originated from cache
     * @param array $meta Additional metadata
     */
    public function __construct(
        public readonly array $data,
        public readonly string $reportType = 'report',
        public readonly bool $fromCache = false,
        public readonly array $meta = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'success'     => true,
            'report_type' => $this->reportType,
            'from_cache'  => $this->fromCache,
            'count'       => count($this->data),
            'timestamp'   => now()->toIso8601String(),
            'data'        => $this->data,
            'meta'        => $this->meta,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
