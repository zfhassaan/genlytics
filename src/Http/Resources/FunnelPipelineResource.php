<?php

namespace zfhassaan\genlytics\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use zfhassaan\genlytics\DTOs\FunnelPipelineDTO;
use zfhassaan\genlytics\Http\Resources\Concerns\HasGenlyticsVersion;

/**
 * Standardized API Resource for Funnel Pipelines
 */
class FunnelPipelineResource extends JsonResource
{
    use HasGenlyticsVersion;
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof FunnelPipelineDTO) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
