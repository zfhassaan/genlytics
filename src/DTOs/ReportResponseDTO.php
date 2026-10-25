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
        public array $data,
        public string $reportType = 'report',
        public bool $fromCache = false,
        public array $meta = []
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
