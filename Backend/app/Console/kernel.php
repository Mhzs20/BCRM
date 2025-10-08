<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Send SMS reminders every minute
        $schedule->command('sms:send-reminders')->everyMinute();

        // Cancel past appointments - runs every 5 minutes for optimal user experience
        $schedule->command('appointments:cancel-past')->everyFiveMinutes();

        // Update reminder SMS status every 5 minutes
        $schedule->command('sms:update-reminder-status')->everyFiveMinutes();

        // Send renewal reminders daily at 10:00 AM
        $schedule->command('reminders:send-renewal')->dailyAt('10:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php'); //
    }
}
