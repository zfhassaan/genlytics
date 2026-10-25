<?php

namespace zfhassaan\genlytics\Exceptions;

use Exception;

class QuotaExceededException extends Exception
{
    protected string $quotaType;
    protected int $limit;
    protected int $consumed;
    protected int $resetInSeconds;

    public function __construct(
        string $quotaType,
        int $limit,
        int $consumed,
        int $resetInSeconds = 0,
        string $message = '',
        int $code = 429,
        ?\Throwable $previous = null
    ) {
        $this->quotaType = $quotaType;
        $this->limit = $limit;
        $this->consumed = $consumed;
        $this->resetInSeconds = $resetInSeconds;

        if (empty($message)) {
            $message = sprintf(
                'Google Analytics 4 API quota exceeded for "%s". Consumed: %d, Limit: %d. Resets in %d seconds.',
                $quotaType,
                $consumed,
                $limit,
                $resetInSeconds
            );
        }

        parent::__construct($message, $code, $previous);
    }

    public function getQuotaType(): string
    {
        return $this->quotaType;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getConsumed(): int
    {
        return $this->consumed;
    }

    public function getResetInSeconds(): int
    {
        return $this->resetInSeconds;
    }
}

