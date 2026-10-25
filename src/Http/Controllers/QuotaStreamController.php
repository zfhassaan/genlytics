<?php

namespace zfhassaan\genlytics\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use zfhassaan\genlytics\Contracts\QuotaManagerInterface;
use zfhassaan\genlytics\Http\Resources\QuotaStatusResource;

/**
 * Controller providing real-time quota telemetry and Server-Sent Events (SSE) streaming.
 */
class QuotaStreamController
{
    public function __construct(
        protected QuotaManagerInterface $quotaManager
    ) {
    }

    /**
     * Get instantaneous JSON snapshot of GA4 quota status.
     */
    public function status(Request $request): JsonResponse
    {
        return response()->json(
            new QuotaStatusResource($this->quotaManager->getStatus())
        );
    }

    /**
     * Server-Sent Events (SSE) stream yielding real-time quota metrics to live frontends.
     */
    public function stream(Request $request): StreamedResponse
    {
        $response = new StreamedResponse(function () {
            // Send initial connection event
            echo "event: connected\n";
            echo 'data: ' . json_encode(['status' => 'connected', 'timestamp' => date('c')]) . "\n\n";
            ob_flush();
            flush();

            // Stream up to 30 snapshots with heartbeat
            for ($i = 0; $i < 30; $i++) {
                if (connection_aborted()) {
                    break;
                }

                $status = $this->quotaManager->getStatus();
                echo "event: quota.updated\n";
                echo 'data: ' . json_encode((new QuotaStatusResource($status))->toArray(request())) . "\n\n";

                ob_flush();
                flush();
                sleep(1);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-transform');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }
}

