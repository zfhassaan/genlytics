<?php

namespace zfhassaan\genlytics\overrides;

use zfhassaan\genlytics\overrides\Call;

/**
 * Middleware to add fixed headers to an API call.
 */
class FixedHeaderMiddleware
{
    /** @var callable */
    private $nextHandler;

    private $headers;
    private $overrideUserHeaders;

    public function __construct(
        callable $nextHandler,
        array $headers,
        bool $overrideUserHeaders = false
    ) {
        $this->nextHandler = $nextHandler;
        $this->headers = $headers;
        $this->overrideUserHeaders = $overrideUserHeaders;
    }

    public function __invoke(Call $call, array $options)
    {
        $userHeaders = isset($options['headers']) ? $options['headers'] : [];
        if ($this->overrideUserHeaders) {
            $options['headers'] = $this->headers + $userHeaders;
        } else {
            $options['headers'] = $userHeaders + $this->headers;
        }

        $next = $this->nextHandler;
        return $next(
            $call,
            $options
        );
    }
}
