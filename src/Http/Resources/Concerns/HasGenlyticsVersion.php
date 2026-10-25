<?php

namespace zfhassaan\genlytics\Http\Resources\Concerns;

use Illuminate\Http\Request;
use zfhassaan\genlytics\Genlytics;

trait HasGenlyticsVersion
{
    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
            'version' => Genlytics::VERSION,
            'meta'    => [
                'version'   => Genlytics::VERSION,
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }
}

