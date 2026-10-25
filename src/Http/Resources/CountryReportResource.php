<?php

namespace zfhassaan\genlytics\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use zfhassaan\genlytics\DTOs\CountryReportDTO;
use zfhassaan\genlytics\Http\Resources\Concerns\HasGenlyticsVersion;

/**
 * Standardized API Resource for Country Reports
 */
class CountryReportResource extends JsonResource
{
    use HasGenlyticsVersion;
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof CountryReportDTO) {
            return $this->resource->toArray();
        }

        $row = is_array($this->resource) ? $this->resource : (array) $this->resource;
        return CountryReportDTO::fromRow($row)->toArray();
    }
}
