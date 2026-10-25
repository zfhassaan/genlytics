<?php

namespace zfhassaan\genlytics\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use zfhassaan\genlytics\DTOs\AnalyticsRowDTO;
use zfhassaan\genlytics\Http\Resources\Concerns\HasGenlyticsVersion;

/**
 * Standardized Base API Resource for Genlytics Records
 */
class AnalyticsResource extends JsonResource
{
    use HasGenlyticsVersion;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof AnalyticsRowDTO) {
            return [
                'dimensions' => $this->resource->dimensions,
                'metrics'    => $this->resource->metrics,
            ];
        }

        if ($this->resource instanceof Arrayable) {
            return $this->resource->toArray();
        }

        if (is_array($this->resource)) {
            // Handle row with dimensions and metrics
            if (isset($this->resource['dimensions']) || isset($this->resource['metrics'])) {
                return [
                    'dimensions' => $this->resource['dimensions'] ?? [],
                    'metrics'    => $this->resource['metrics'] ?? [],
                ];
            }

            return $this->resource;
        }

        return (array) $this->resource;
    }
}
