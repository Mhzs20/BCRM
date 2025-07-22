<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Jobs\SendSatisfactionSurveySms;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;
use Hashids\Hashids;

class AppointmentObserver
{
    /**
     * Handle the Appointment "updated" event.
     */
    public function updated(Appointment $appointment): void
    {
        // Check if the status changed to 'completed' and satisfaction SMS is enabled
        if ($appointment->isDirty('status') && $appointment->status === 'completed' && $appointment->send_satisfaction_sms) {
            // Ensure customer and salon exist
            if ($appointment->customer && $appointment->salon) {
                // Dispatch the job
                SendSatisfactionSurveySms::dispatch($appointment, $appointment->salon);
                Log::info("Dispatched satisfaction survey SMS job for completed appointment {$appointment->id}.");
            } else {
                Log::warning("Cannot dispatch satisfaction survey SMS for appointment {$appointment->id}: customer or salon not found.");
            }
        }
    }

    /**
     * Handle the Appointment "created" event.
     */
    public function created(Appointment $appointment): void
    {
        // Generate a unique hash for the appointment
        $hashids = new Hashids(env('HASHIDS_SALT', 'your-default-salt'), 8);
        $hash = $hashids->encode($appointment->id);
        $appointment->hash = $hash;
        $appointment->saveQuietly();

        if ($appointment->customer && $appointment->salon) {
            try {
                $smsService = new SmsService();
                $detailsUrl = route('appointments.show.hash', ['hash' => $appointment->hash]);
                $smsService->sendAppointmentConfirmation($appointment->customer, $appointment, $appointment->salon, $detailsUrl);
                Log::info("Dispatched appointment confirmation SMS for appointment {$appointment->id}.");
            } catch (\Exception $e) {
                Log::error("Failed to send appointment confirmation SMS for appointment {$appointment->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle the Appointment "deleted" event.
     */
    public function deleted(Appointment $appointment): void
    {
        //
    }

    /**
     * Handle the Appointment "restored" event.
     */
    public function restored(Appointment $appointment): void
    {
        //
    }

    /**
     * Handle the Appointment "forceDeleted" event.
     */
    public function forceDeleted(Appointment $appointment): void
    {
        //
    }
}
