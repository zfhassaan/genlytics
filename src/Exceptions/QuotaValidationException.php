<?php

namespace zfhassaan\genlytics\Exceptions;

use InvalidArgumentException;

class QuotaValidationException extends InvalidArgumentException
{
    protected string $field;
    protected mixed $invalidValue;
    protected mixed $expected;

    public function __construct(
        string $field,
        mixed $invalidValue,
        mixed $expected,
        string $message = '',
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        $this->field = $field;
        $this->invalidValue = $invalidValue;
        $this->expected = $expected;

        if (empty($message)) {
            $message = sprintf(
                'Invalid GA4 Quota configuration for "%s": received %s. Expected: %s.',
                $field,
                is_scalar($invalidValue) ? (string) $invalidValue : gettype($invalidValue),
                is_scalar($expected) ? (string) $expected : json_encode($expected)
            );
        }

        parent::__construct($message, $code, $previous);
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getInvalidValue(): mixed
    {
        return $this->invalidValue;
    }

    public function getExpected(): mixed
    {
        return $this->expected;
    }
}
