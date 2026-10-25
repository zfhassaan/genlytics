<?php

namespace zfhassaan\genlytics\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use zfhassaan\genlytics\DTOs\CategoryPerformanceDTO;
use zfhassaan\genlytics\Http\Resources\Concerns\HasGenlyticsVersion;

/**
 * Standardized API Resource for Category Performance
 */
class CategoryPerformanceResource extends JsonResource
{
    use HasGenlyticsVersion;
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof CategoryPerformanceDTO) {
            return $this->resource->toArray();
        }

        $row = is_array($this->resource) ? $this->resource : (array) $this->resource;
        return CategoryPerformanceDTO::fromRow($row)->toArray();
    }
}
