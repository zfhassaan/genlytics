<?php

namespace zfhassaan\genlytics\overrides;

use Google\ApiCore\BidiStream;
use zfhassaan\genlytics\overrides\Call;
use Google\ApiCore\ClientStream;
use Google\ApiCore\ServerStream;
use Google\ApiCore\ValidationException;
use GuzzleHttp\Promise\PromiseInterface;

interface TransportInterface
{
    /**
     * Starts a bidi streaming call.
     *
     * @param Call $call
     * @param array<mixed> $options
     *
     * @return BidiStream
     */
    public function startBidiStreamingCall(Call $call, array $options);

    /**
     * Starts a client streaming call.
     *
     * @param Call $call
     * @param array<mixed> $options
     *
     * @return ClientStream
     */
    public function startClientStreamingCall(Call $call, array $options);

    /**
     * Starts a server streaming call.
     *
     * @param Call $call
     * @param array<mixed> $options
     *
     * @return ServerStream
     */
    public function startServerStreamingCall(Call $call, array $options);

    /**
     * Returns a promise used to execute network requests.
     *
     * @param Call $call
     * @param array<mixed> $options
     *
     * @return PromiseInterface
     * @throws ValidationException
     */
    public function startUnaryCall(Call $call, array $options);

    /**
     * Closes the connection, if one exists.
     *
     * @return void
     */
    public function close();
}
