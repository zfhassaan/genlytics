<?php

namespace zfhassaan\genlytics\overrides;


use Google\ApiCore\ApiException;
use Google\ApiCore\ApiStatus;
//use Google\ApiCore\Call;
use zfhassaan\genlytics\overrides\Call;
//use Google\ApiCore\RetrySettings;
use zfhassaan\genlytics\overrides\RetrySettings;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Middleware that adds retry functionality.
 */
class RetryMiddleware
{
    /** @var callable */
    private $nextHandler;

    /** @var RetrySettings */
    private $retrySettings;

    /** @var float|null */
    private $deadlineMs;

    public function __construct(
        callable $nextHandler,
        RetrySettings $retrySettings,
        $deadlineMs = null
    ) {
        $this->nextHandler = $nextHandler;
        $this->retrySettings = $retrySettings;
        $this->deadlineMs = $deadlineMs;
    }

    /**
     * @param Call $call
     * @param array $options
     *
     * @return PromiseInterface
     */
    public function __invoke(Call $call, array $options)
    {
        $nextHandler = $this->nextHandler;

        if (!isset($options['timeoutMillis'])) {
            // default to "noRetriesRpcTimeoutMillis" when retries are disabled, otherwise use "initialRpcTimeoutMillis"
            if (!$this->retrySettings->retriesEnabled() && $this->retrySettings->getNoRetriesRpcTimeoutMillis() > 0) {
                $options['timeoutMillis'] = $this->retrySettings->getNoRetriesRpcTimeoutMillis();
            } elseif ($this->retrySettings->getInitialRpcTimeoutMillis() > 0) {
                $options['timeoutMillis'] = $this->retrySettings->getInitialRpcTimeoutMillis();
            }
        }

        // Call the handler immediately if retry settings are disabled.
        if (!$this->retrySettings->retriesEnabled()) {
            return $nextHandler($call, $options);
        }

        return $nextHandler($call, $options)->then(null, function ($e) use ($call, $options) {
            if (!$e instanceof ApiException) {
                throw $e;
            }

            if (!in_array($e->getStatus(), $this->retrySettings->getRetryableCodes())) {
                throw $e;
            }

            return $this->retry($call, $options, $e->getStatus());
        });
    }

    /**
     * @param Call $call
     * @param array $options
     * @param string $status
     *
     * @return PromiseInterface
     * @throws ApiException
     */
    private function retry(Call $call, array $options, string $status)
    {
        $delayMult = $this->retrySettings->getRetryDelayMultiplier();
        $maxDelayMs = $this->retrySettings->getMaxRetryDelayMillis();
        $timeoutMult = $this->retrySettings->getRpcTimeoutMultiplier();
        $maxTimeoutMs = $this->retrySettings->getMaxRpcTimeoutMillis();
        $totalTimeoutMs = $this->retrySettings->getTotalTimeoutMillis();

        $delayMs = $this->retrySettings->getInitialRetryDelayMillis();
        $timeoutMs = $options['timeoutMillis'];
        $currentTimeMs = $this->getCurrentTimeMs();
        $deadlineMs = $this->deadlineMs ?: $currentTimeMs + $totalTimeoutMs;

        if ($currentTimeMs >= $deadlineMs) {
            throw new ApiException(
                'Retry total timeout exceeded.',
                \Google\Rpc\Code::DEADLINE_EXCEEDED,
                ApiStatus::DEADLINE_EXCEEDED
            );
        }

        $delayMs = min($delayMs * $delayMult, $maxDelayMs);
        $timeoutMs = min(
            $timeoutMs * $timeoutMult,
            $maxTimeoutMs,
            $deadlineMs - $this->getCurrentTimeMs()
        );

        $nextHandler = new \Google\ApiCore\Middleware\RetryMiddleware(
            $this->nextHandler,
            $this->retrySettings->with([
                'initialRetryDelayMillis' => $delayMs,
            ]),
            $deadlineMs
        );

        // Set the timeout for the call
        $options['timeoutMillis'] = $timeoutMs;

        return $nextHandler(
            $call,
            $options
        );
    }

    protected function getCurrentTimeMs()
    {
        return microtime(true) * 1000.0;
    }
}
