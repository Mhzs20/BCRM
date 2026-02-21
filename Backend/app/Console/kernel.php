<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Cache;

class Kernel extends ConsoleKernel
{
	/**
	 * Define the application's command schedule.
	 */
	protected function schedule(Schedule $schedule): void
	{
		// Send SMS reminders every minute
		$schedule->command('sms:send-reminders')->everyMinute()->withoutOverlapping()
			->before(fn() => $this->trackStart('sms_send_reminders'))
			->after(fn() => $this->trackEnd('sms_send_reminders'))
			->onFailure(fn() => $this->trackError('sms_send_reminders'));

		// Cancel past appointments - runs every 5 minutes for optimal user experience
		$schedule->command('appointments:cancel-past')->everyFiveMinutes()
			->before(fn() => $this->trackStart('appointments_cancel_past'))
			->after(fn() => $this->trackEnd('appointments_cancel_past'))
			->onFailure(fn() => $this->trackError('appointments_cancel_past'));

		// Check and update all pending SMS statuses (appointments + manual SMS transactions)
		$schedule->job(new \App\Jobs\CheckSmsStatus)->everyFiveMinutes()
			->before(fn() => $this->trackStart('CheckSmsStatus'))
			->after(fn() => $this->trackEnd('CheckSmsStatus'))
			->onFailure(fn() => $this->trackError('CheckSmsStatus'));

		// Send service renewal reminders every minute
		$schedule->command('renewal:send-reminders')->everyMinute()->withoutOverlapping()
			->before(fn() => $this->trackStart('renewal_send_reminders'))
			->after(fn() => $this->trackEnd('renewal_send_reminders'))
			->onFailure(fn() => $this->trackError('renewal_send_reminders'));

		// Send birthday reminders every minute (supports any user-set time)
		$schedule->command('reminders:send-birthday')->everyMinute()
			->before(fn() => $this->trackStart('reminders_send_birthday'))
			->after(fn() => $this->trackEnd('reminders_send_birthday'))
			->onFailure(fn() => $this->trackError('reminders_send_birthday'));

		// Process satisfaction surveys every hour
		$schedule->command('satisfaction:process')->hourly()->withoutOverlapping()
			->before(fn() => $this->trackStart('satisfaction_process'))
			->after(fn() => $this->trackEnd('satisfaction_process'))
			->onFailure(fn() => $this->trackError('satisfaction_process'));

		// Process automatic customer followups every hour
		$schedule->command('followup:process-customers')->hourly()->withoutOverlapping()
			->before(fn() => $this->trackStart('followup_process_customers'))
			->after(fn() => $this->trackEnd('followup_process_customers'))
			->onFailure(fn() => $this->trackError('followup_process_customers'));
	}

	/**
	 * Track scheduler task start.
	 */
	private function trackStart(string $key): void
	{
		Cache::put("scheduler_start_{$key}", now()->toIso8601String(), 3600);
	}

	/**
	 * Track scheduler task completion.
	 */
	private function trackEnd(string $key): void
	{
		$startTime = Cache::get("scheduler_start_{$key}");
		$duration = $startTime ? now()->diffInMilliseconds(\Carbon\Carbon::parse($startTime)) / 1000 : null;

		Cache::put("scheduler_last_run_{$key}", now()->toIso8601String(), 86400);
		Cache::put("scheduler_last_run_{$key}_duration", $duration, 86400);
		Cache::forget("scheduler_last_run_{$key}_error");
	}

	/**
	 * Track scheduler task failure.
	 */
	private function trackError(string $key): void
	{
		$startTime = Cache::get("scheduler_start_{$key}");
		$duration = $startTime ? now()->diffInMilliseconds(\Carbon\Carbon::parse($startTime)) / 1000 : null;

		Cache::put("scheduler_last_run_{$key}", now()->toIso8601String(), 86400);
		Cache::put("scheduler_last_run_{$key}_duration", $duration, 86400);
		Cache::put("scheduler_last_run_{$key}_error", 'Task failed at ' . now()->toIso8601String(), 86400);
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

