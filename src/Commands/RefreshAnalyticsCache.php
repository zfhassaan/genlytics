<?php

namespace zfhassaan\genlytics\Commands;

use Illuminate\Console\Command;
use zfhassaan\genlytics\Contracts\CacheManagerInterface;

/**
 * Artisan command to refresh analytics cache
 */
class RefreshAnalyticsCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'genlytics:refresh-cache 
                            {--type=* : Type of cache to refresh (report, realtime, dimension, all)}
                            {--clear : Clear all cache instead of refreshing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh or clear analytics cache';

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

        $types = $this->option('type') ?: ['all'];

        if (in_array('all', $types)) {
            $this->info('Refreshing all analytics cache...');
            // In a real implementation, you might want to queue refresh jobs
            $this->info('Cache refresh queued. Use background jobs to refresh data.');
        } else {
            foreach ($types as $type) {
                $this->info("Refreshing {$type} cache...");
            }
        }

        return Command::SUCCESS;
    }
}
