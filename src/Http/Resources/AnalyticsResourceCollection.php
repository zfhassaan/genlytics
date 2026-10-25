<?php

namespace zfhassaan\genlytics\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use zfhassaan\genlytics\Genlytics;

/**
 * Standardized API Resource Collection with Metadata Wrapper
 */
class AnalyticsResourceCollection extends ResourceCollection
{
    public $collects = AnalyticsResource::class;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
            'version' => Genlytics::VERSION,
            'meta'    => [
                'version'   => Genlytics::VERSION,
                'count'     => $this->collection->count(),
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }
}

