<?php

namespace zfhassaan\genlytics\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use zfhassaan\genlytics\DTOs\QuotaStatusDTO;

/**
 * Event fired in real-time whenever GA4 quota usage is recorded or synchronized.
 * Broadcasts across Laravel Echo, Reverb, Pusher, or Soketi for live dashboards.
 */
class QuotaUsageUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param QuotaStatusDTO $status Current snapshot of GA4 quota status
     * @param int $tokensConsumed Tokens consumed in the latest operation
     * @param string $channel Channel name to broadcast on
     */
    public function __construct(
        public QuotaStatusDTO $status,
        public int $tokensConsumed = 0,
        public string $channel = 'genlytics-quota'
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel($this->channel),
            new PrivateChannel('genlytics-quota.' . $this->status->propertyType),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'quota.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'property_type' => $this->status->propertyType,
            'mode' => $this->status->mode,
            'daily_tokens_limit' => $this->status->dailyTokensLimit,
            'daily_tokens_consumed' => $this->status->dailyTokensConsumed,
            'daily_tokens_remaining' => $this->status->dailyTokensRemaining,
            'daily_percent' => $this->status->dailyUtilizationPercent,
            'hourly_tokens_limit' => $this->status->hourlyTokensLimit,
            'hourly_tokens_consumed' => $this->status->hourlyTokensConsumed,
            'hourly_tokens_remaining' => $this->status->hourlyTokensRemaining,
            'hourly_percent' => $this->status->hourlyUtilizationPercent,
            'concurrent_active' => $this->status->concurrentRequestsActive,
            'pacing_status' => $this->status->pacingStatus,
            'seconds_until_pst_reset' => $this->status->secondsUntilDailyReset,
            'daily_reset_at_pst' => $this->status->dailyResetAtPst,
            'tokens_consumed_this_event' => $this->tokensConsumed,
            'consume_all_active' => ($this->status->mode === 'consume_all' || $this->status->maxUtilizationEnabled),
            'timestamp' => date('c'),
        ];
    }
}
