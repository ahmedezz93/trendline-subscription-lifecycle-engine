<?php

namespace App\Console\Commands;

use App\Services\SubscriptionLifecycleService;
use Illuminate\Console\Command;

class ProcessSubscriptionLifecycleCommand extends Command
{
    protected $signature = 'subscriptions:process-lifecycle';
    protected $description = 'Process expired trials and past_due subscriptions that exceeded the grace period';

    public function handle(SubscriptionLifecycleService $lifecycleService): int
    {
        $result = $lifecycleService->processDailyLifecycle();

        $this->info("Expired trials processed: {$result['expired_trials_processed']}");
        $this->info("Grace period cancellations processed: {$result['grace_period_cancellations_processed']}");

        return self::SUCCESS;
    }
}
