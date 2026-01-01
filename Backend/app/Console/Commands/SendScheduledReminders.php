<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Jobs\SendAppointmentReminderSms;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendScheduledReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends reminder SMS for upcoming appointments.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Prevent concurrent execution using Cache lock key
        $lock = \Illuminate\Support\Facades\Cache::lock('sms_send_reminders_lock', 50);

        if (!$lock->get()) {
            Log::info('sms:send-reminders command is already running. Skipping this execution.');
            return;
        }

        try {
            Log::info('Running sms:send-reminders command.');

            $now = Carbon::now();

            // More efficient query to fetch only appointments that are due for a reminder.
            // This calculates the reminder time in the database.
            $appointments = Appointment::where('send_reminder_sms', true)
                ->where(function ($query) {
                    $query->where('reminder_sms_status', 'not_sent')
                          ->orWhereNull('reminder_sms_status');
                })
                ->whereNotNull('reminder_time')
                ->where('status', '!=', 'canceled') // Exclude canceled appointments
                ->whereHas('customer')
                ->whereHas('salon')
                // The core logic: check if the current time is past the calculated reminder time.
                // Assumes reminder_time is in hours.
                ->whereRaw('NOW() >= DATE_SUB(CONCAT(appointment_date, " ", start_time), INTERVAL reminder_time HOUR)')
                // And ensure we don't send reminders for past appointments.
                ->whereRaw('NOW() < CONCAT(appointment_date, " ", start_time)')
                ->get();

            foreach ($appointments as $appointment) {
                try {
                    // Dispatch the job for each due appointment
                    SendAppointmentReminderSms::dispatch($appointment, $appointment->salon);
                    Log::info("Dispatched reminder SMS job for appointment {$appointment->id}.");

                    // Mark as processing to avoid duplicate sends on the next run
                    $appointment->update(['reminder_sms_status' => 'processing']);

                } catch (\Exception $e) {
                    Log::error("Error dispatching reminder for appointment {$appointment->id}: " . $e->getMessage());
                }
            }

            Log::info('Finished sms:send-reminders command.');
        } finally {
            $lock->release();
        }
    }
}
