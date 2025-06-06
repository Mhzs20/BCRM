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
    protected $commands = [
        Commands\SendAppointmentReminders::class,
        Commands\UpdatePastAppointmentsStatus::class,
        Commands\SendBirthdayGreetings::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // پیامک یادآوری نوبت
        $schedule->command('sms:send-reminders')
            ->dailyAt('09:00'); // مثال: هر روز ساعت ۹ صبح (قابل تنظیم)

        // به‌روزرسانی وضعیت نوبت‌های گذشته به "انجام‌شده"
        $schedule->command('appointments:update-status')
            ->hourly(); // مثال: هر ساعت

        // ارسال پیام تبریک تولد
        $schedule->command('sms:send-birthday-greetings')
            ->dailyAt('08:00'); // مثال: هر روز ساعت ۸ صبح
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
