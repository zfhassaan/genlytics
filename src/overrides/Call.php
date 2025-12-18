<?php

namespace zfhassaan\genlytics\overrides;

use Google\Protobuf\Internal\Message;

/**
 * Contains information necessary to manage a network request.
 */
class Call
{
    public const UNARY_CALL = 0;
    public const BIDI_STREAMING_CALL = 1;
    public const CLIENT_STREAMING_CALL = 2;
    public const SERVER_STREAMING_CALL = 3;
    public const LONGRUNNING_CALL = 4;
    public const PAGINATED_CALL = 5;

    private $method;
    private $callType;
    private $decodeType;
    private $message;
    private $descriptor;

    /**
     * @param string $method
     * @param string $decodeType
     * @param mixed|Message $message
     * @param array|null $descriptor
     * @param int $callType
     */
    public function __construct(
        string $method,
        string $decodeType = null,
        $message = null,
        $descriptor = [],
        int $callType = \Google\ApiCore\Call::UNARY_CALL
    ) {
        $this->method = $method;
        $this->decodeType = $decodeType;
        $this->message = $message;
        $this->descriptor = $descriptor;
        $this->callType = $callType;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return int
     */
    public function getCallType()
    {
        return $this->callType;
    }

    /**
     * @return string
     */
    public function getDecodeType()
    {
        return $this->decodeType;
    }

    /**
     * @return mixed|Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return array|null
     */
    public function getDescriptor()
    {
        return $this->descriptor;
    }

    /**
     * @param mixed|Message $message
     * @return Call
     */
    public function withMessage($message)
    {
        // @phpstan-ignore-next-line
        return new static(
            $this->method,
            $this->decodeType,
            $message,
            $this->descriptor,
            $this->callType
        );
    }
}
