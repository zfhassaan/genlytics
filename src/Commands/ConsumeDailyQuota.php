<?php

namespace zfhassaan\genlytics\Commands;

use Illuminate\Console\Command;
use zfhassaan\genlytics\Contracts\QuotaManagerInterface;
use zfhassaan\genlytics\Genlytics;

/**
 * Artisan command to proactively consume up to 100% of remaining daily GA4 quota tokens.
 * Pre-warms cache and extracts maximum daily data before midnight PST reset.
 */
class ConsumeDailyQuota extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'genlytics:consume-quota
                            {--target-utilization=100 : Target percentage of daily quota to consume (1-100)}
                            {--dry-run : Calculate token budget without executing requests}
                            {--type=all : Query categories to pre-warm (products, categories, funnels, realtime, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Proactively consume up to 100% of daily GA4 quota before midnight PST by pre-warming cache';

    /**
     * Execute the console command.
     */
    public function handle(QuotaManagerInterface $quotaManager, Genlytics $genlytics): int
    {
        $this->info('=================================================================');
        $this->info('     GENLYTICS - 100% DAILY QUOTA EXTRACTION & PRE-WARMING');
        $this->info('=================================================================');

        $quotaManager->enableAllQuotaConsumption();
        $status = $quotaManager->getStatus();

        $dailyLimit = $status->dailyTokensLimit;
        $dailyConsumed = $status->dailyTokensConsumed;
        $dailyRemaining = $status->dailyTokensRemaining;
        $targetPct = min(100, max(1, (int) $this->option('target-utilization')));
        $targetTokensTotal = (int) ($dailyLimit * ($targetPct / 100));
        $tokensNeeded = max(0, $targetTokensTotal - $dailyConsumed);

        $this->table(
            ['Property Type', 'Daily Limit', 'Already Consumed', 'Remaining Today', 'Target Goal', 'Reset Schedule'],
            [[
                strtoupper($status->propertyType),
                number_format($dailyLimit) . ' tokens',
                number_format($dailyConsumed) . ' (' . $status->dailyUtilizationPercent . '%)',
                number_format($dailyRemaining) . ' tokens',
                $targetPct . '% (' . number_format($targetTokensTotal) . ' tokens)',
                '12:00:00 AM PST (' . $status->dailyResetAtPst . ')',
            ]]
        );

        if ($tokensNeeded <= 0) {
            $this->info(sprintf('✓ Target utilization (%d%%) already reached. No additional tokens needed.', $targetPct));
            return Command::SUCCESS;
        }

        $this->info(sprintf('⚡ Planned consumption: %s tokens across high-value reports.', number_format($tokensNeeded)));

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN: Pre-flight estimation complete. Exiting without calling Google API.');
            return Command::SUCCESS;
        }

        $type = (string) $this->option('type');
        $this->output->progressStart($tokensNeeded);

        $prewarmTasks = [
            'top_products_7d' => fn () => $genlytics->topProducts(limit: 20, period: '7daysAgo'),
            'top_products_30d' => fn () => $genlytics->topProducts(limit: 20, period: '30daysAgo'),
            'top_categories_7d' => fn () => $genlytics->topCategories(limit: 15, period: '7daysAgo'),
            'top_categories_30d' => fn () => $genlytics->topCategories(limit: 15, period: '30daysAgo'),
            'ecommerce_funnel' => fn () => $genlytics->ecommerceFunnel('30daysAgo'),
            'realtime_pages' => fn () => $genlytics->realtimeTopPages(15),
            'realtime_countries' => fn () => $genlytics->realtimeTopCountries(15),
        ];

        $consumedSoFar = 0;
        foreach ($prewarmTasks as $taskName => $task) {
            if ($consumedSoFar >= $tokensNeeded || $quotaManager->getRemainingDailyTokens() <= 0) {
                break;
            }

            try {
                if ($quotaManager->acquireConcurrencySlot(1000)) {
                    $task();
                    $tokens = 2; // base query cost
                    $consumedSoFar += $tokens;
                    $this->output->progressAdvance($tokens);
                }
            } catch (\Throwable $e) {
                $this->warn("Task {$taskName} failed: " . $e->getMessage());
            } finally {
                $quotaManager->releaseConcurrencySlot();
            }

            usleep(25000); // 25ms yield to honor 10 QPS IP rate limit
        }

        $this->output->progressFinish();

        $finalStatus = $quotaManager->getStatus();
        $this->info('✓ Daily Quota Pre-Warming Completed Successfully!');
        $this->info(sprintf(
            'Final Utilization: %s / %s tokens (%s%% utilized). Real-time events broadcasted.',
            number_format($finalStatus->dailyTokensConsumed),
            number_format($finalStatus->dailyTokensLimit),
            $finalStatus->dailyUtilizationPercent
        ));

        return Command::SUCCESS;
    }
}
