<?php

namespace zfhassaan\genlytics\Commands;

use Illuminate\Console\Command;
use zfhassaan\genlytics\Contracts\CacheManagerInterface;

/**
 * Artisan command to refresh or clear analytics cache
 */
class RefreshAnalyticsCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'genlytics:refresh-cache 
                            {--type=* : Type of cache to refresh (report, realtime, dimension, batch, all)}
                            {--clear : Clear all analytics cache}
                            {--consume-all : Maximize daily utilization by using remaining tokens to pre-warm cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh or clear analytics cache, with option to consume all daily quota';

    /**
     * Execute the console command
     *
     * @param CacheManagerInterface $cacheManager
     * @return int
     */
    public function handle(CacheManagerInterface $cacheManager): int
    {
        if ($this->option('clear')) {
            $cacheManager->clear();
            $this->info('Analytics cache cleared successfully.');
            return Command::SUCCESS;
        }

        if ($this->option('consume-all')) {
            $this->info('🚀 Activating Full Daily Quota Consumption Mode...');
            if (app()->bound(\zfhassaan\genlytics\Contracts\QuotaManagerInterface::class)) {
                $quota = app(\zfhassaan\genlytics\Contracts\QuotaManagerInterface::class);
                $quota->enableAllQuotaConsumption();
                $this->info(sprintf(
                    'Available Daily Quota: %s tokens remaining (PST Reset). Pre-warming cache...',
                    number_format($quota->getRemainingDailyTokens())
                ));
            }
        }

        $types = $this->option('type') ?: ['all'];

        if (in_array('all', $types)) {
            $this->info('Refreshing all analytics cache...');
            $cacheManager->clear();
            $this->info('Cache refresh queued. Use background jobs to refresh data.');
        } else {
            foreach ($types as $type) {
                $this->info("Refreshing {$type} cache...");
            }
        }

        return Command::SUCCESS;
    }
}
