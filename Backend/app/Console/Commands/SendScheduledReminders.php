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
        Log::info('Running sms:send-reminders command.');

        $now = Carbon::now();

        // Fetch appointments that are due for a reminder
        $appointments = Appointment::where('send_reminder_sms', true)
            ->where('reminder_sms_status', 'not_sent')
            ->whereNotNull('reminder_time')
            ->whereHas('customer') // Ensure customer exists
            ->whereHas('salon') // Ensure salon exists
            ->get();

        foreach ($appointments as $appointment) {
            try {
                $appointmentDateTime = Carbon::parse($appointment->appointment_date . ' ' . $appointment->start_time);
                $reminderDueTime = $appointmentDateTime->subHours($appointment->reminder_time);

                if ($now->greaterThanOrEqualTo($reminderDueTime) && $now->lessThan($appointmentDateTime)) {
                    // Dispatch the job
                    SendAppointmentReminderSms::dispatch($appointment, $appointment->salon);
                    Log::info("Dispatched reminder SMS job for appointment {$appointment->id}.");
                }
            } catch (\Exception $e) {
                Log::error("Error processing reminder for appointment {$appointment->id}: " . $e->getMessage());
            }
        }

        Log::info('Finished sms:send-reminders command.');
    }
}
