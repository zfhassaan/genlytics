<?php

namespace zfhassaan\genlytics\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use zfhassaan\genlytics\DTOs\ProductPerformanceDTO;
use zfhassaan\genlytics\Http\Resources\Concerns\HasGenlyticsVersion;

/**
 * Standardized API Resource for Product Performance
 */
class ProductPerformanceResource extends JsonResource
{
    use HasGenlyticsVersion;
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof ProductPerformanceDTO) {
            return $this->resource->toArray();
        }

        $row = is_array($this->resource) ? $this->resource : (array) $this->resource;
        return ProductPerformanceDTO::fromRow($row)->toArray();
    }
}
