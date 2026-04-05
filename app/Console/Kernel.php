<?php

namespace App\Console;

use App\Console\Commands\ProcessSubscriptionLifecycleCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        ProcessSubscriptionLifecycleCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('subscriptions:process-lifecycle')->daily();
    }
}
