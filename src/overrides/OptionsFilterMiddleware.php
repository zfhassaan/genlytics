<?php

namespace zfhassaan\genlytics\overrides;

use Google\ApiCore\ArrayTrait;
use zfhassaan\genlytics\overrides\Call;

/**
 * Middleware which filters the $options array.
 */
class OptionsFilterMiddleware
{
    use ArrayTrait;

    /** @var callable */
    private $nextHandler;

    /** @var array */
    private $permittedOptions;

    public function __construct(
        callable $nextHandler,
        array $permittedOptions
    ) {
        $this->nextHandler = $nextHandler;
        $this->permittedOptions = $permittedOptions;
    }

    public function __invoke(Call $call, array $options)
    {
        $next = $this->nextHandler;
        $filteredOptions = $this->pluckArray($this->permittedOptions, $options);
        return $next(
            $call,
            $filteredOptions
        );
    }
}
