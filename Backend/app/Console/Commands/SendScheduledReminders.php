<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Services\SmsService;
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
    public function handle(SmsService $smsService): void
    {
        // Prevent concurrent execution using Cache lock key.
        // TTL = 300s: since sending is now synchronous (no queue),
        // a busy run with many reminders can exceed 50s. 300s gives enough headroom
        // while still auto-expiring if the process crashes without releasing the lock.
        $lock = \Illuminate\Support\Facades\Cache::lock('sms_send_reminders_lock', 300);

        if (!$lock->get()) {
            Log::info('sms:send-reminders command is already running. Skipping this execution.');
            return;
        }

        try {
            Log::info('Running sms:send-reminders command.');

            // IMPORTANT: Use Carbon::now() bound as a PHP parameter instead of MySQL's NOW().
            // MySQL's NOW() returns UTC time, but appointment_date/start_time are stored in
            // Asia/Tehran (UTC+3:30). Using MySQL NOW() causes reminders to fire ~3.5 hours late.
            // By binding the Tehran-timezone Carbon time as a SQL parameter, both sides of the
            // comparison are in the same timezone.
            $now = Carbon::now(); // Uses app timezone = Asia/Tehran as configured in config/app.php
            $nowString = $now->format('Y-m-d H:i:s');

            // --- Step 1: Recover stuck "processing" appointments ---
            // If a previous run dispatched to the queue but the queue worker was down,
            // the job never ran and the appointment is permanently stuck at 'processing'.
            // After 10 minutes, we consider it stale and retry.
            $staleThreshold = $now->copy()->subMinutes(10)->format('Y-m-d H:i:s');

            $staleAppointments = Appointment::where('send_reminder_sms', true)
                ->where('reminder_sms_status', 'processing')
                ->whereNotNull('reminder_time')
                ->where('status', '!=', 'canceled')
                ->whereHas('customer')
                ->whereHas('salon')
                ->where('updated_at', '<', $staleThreshold)
                // Still within appointment window
                ->whereRaw('? < CONCAT(appointment_date, " ", start_time)', [$nowString])
                ->get();

            if ($staleAppointments->isNotEmpty()) {
                Log::warning("Found {$staleAppointments->count()} stale 'processing' reminder(s). Resetting to 'not_sent' for retry.");
                foreach ($staleAppointments as $apt) {
                    $apt->update(['reminder_sms_status' => 'not_sent']);
                }
            }

            // --- Step 2: Find appointments due for a reminder ---
            $appointments = Appointment::where('send_reminder_sms', true)
                ->where(function ($query) {
                    $query->where('reminder_sms_status', 'not_sent')
                          ->orWhereNull('reminder_sms_status');
                })
                ->whereNotNull('reminder_time')
                ->where('status', '!=', 'canceled')
                ->whereHas('customer')
                ->whereHas('salon')
                // Core logic: current Tehran time has passed the (appointment_time - reminder_time).
                // Binding PHP Carbon time avoids MySQL UTC vs Tehran timezone mismatch.
                ->whereRaw('? >= DATE_SUB(CONCAT(appointment_date, " ", start_time), INTERVAL reminder_time HOUR)', [$nowString])
                // Don't send for appointments that have already started.
                ->whereRaw('? < CONCAT(appointment_date, " ", start_time)', [$nowString])
                ->with(['customer', 'salon', 'salon.user', 'services', 'staff'])
                ->get();

            if ($appointments->isEmpty()) {
                Log::info('sms:send-reminders: No reminders due.');
                return;
            }

            Log::info("sms:send-reminders: Found {$appointments->count()} reminder(s) to send.");

            foreach ($appointments as $appointment) {
                try {
                    $customer = $appointment->customer;
                    $salon    = $appointment->salon;

                    if (!$customer || !$salon) {
                        Log::warning("sms:send-reminders: Skipping appointment {$appointment->id} — missing customer or salon.");
                        continue;
                    }

                    // Mark as processing immediately to prevent duplicate sends on next tick
                    $appointment->update(['reminder_sms_status' => 'processing']);

                    // Send synchronously — no queue dependency for time-critical reminders.
                    // SmsService::sendAppointmentReminder() updates reminder_sms_status itself.
                    $smsService->sendAppointmentReminder($customer, $appointment, $salon);

                    Log::info("sms:send-reminders: Reminder sent for appointment {$appointment->id}.");

                } catch (\Exception $e) {
                    Log::error("sms:send-reminders: Error sending reminder for appointment {$appointment->id}: " . $e->getMessage());
                    // Reset to not_sent so we retry on the next minute
                    $appointment->update(['reminder_sms_status' => 'not_sent']);
                }
            }

            Log::info('sms:send-reminders: Finished.');
        } finally {
            $lock->release();
        }
    }
}
