<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
	/**
	 * Define the application's command schedule.
	 */
	protected function schedule(Schedule $schedule): void
	{
		// Send SMS reminders every minute
		$schedule->command('sms:send-reminders')->everyMinute()->withoutOverlapping();

		// Cancel past appointments - runs every 5 minutes for optimal user experience
		$schedule->command('appointments:cancel-past')->everyFiveMinutes();

		// Check and update all pending SMS statuses (appointments + manual SMS transactions)
		$schedule->job(new \App\Jobs\CheckSmsStatus)->everyFiveMinutes();

		// Send service renewal reminders every minute
		$schedule->command('renewal:send-reminders')->everyMinute()->withoutOverlapping();

		// Send birthday reminders every minute (supports any user-set time)
		$schedule->command('reminders:send-birthday')->everyMinute();

		// Process satisfaction surveys every hour
		$schedule->command('satisfaction:process')->hourly()->withoutOverlapping();

		// Process automatic customer followups every hour
		$schedule->command('followup:process-customers')->hourly()->withoutOverlapping();
	}

	/**
	 * Register the commands for the application.
	 */

	protected function commands(): void
	{
		$this->load(__DIR__.'/Commands');

		require base_path('routes/console.php');
	}
}

