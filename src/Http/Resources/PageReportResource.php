<?php

namespace zfhassaan\genlytics\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use zfhassaan\genlytics\DTOs\PageReportDTO;
use zfhassaan\genlytics\Http\Resources\Concerns\HasGenlyticsVersion;

/**
 * Standardized API Resource for Page Reports
 */
class PageReportResource extends JsonResource
{
    use HasGenlyticsVersion;
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof PageReportDTO) {
            return $this->resource->toArray();
        }

        $row = is_array($this->resource) ? $this->resource : (array) $this->resource;
        return PageReportDTO::fromRow($row)->toArray();
    }
}

