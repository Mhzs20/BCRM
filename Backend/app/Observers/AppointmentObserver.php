<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Jobs\SendAppointmentModificationSms;
use App\Jobs\SendSatisfactionSurveySms;
use Hashids\Hashids;

class AppointmentObserver
{
    /**
     * Handle the Appointment "updated" event.
     *
     * @param  \App\Models\Appointment  $appointment
     * @return void
     */
    public function updated(Appointment $appointment)
    {
        if ($appointment->isDirty('status') && $appointment->status === 'done') {
            SendSatisfactionSurveySms::dispatch($appointment);
        }
        elseif ($appointment->isDirty('status') || $appointment->isDirty('appointment_date') || $appointment->isDirty('start_time')) {
            // Regenerate the hash to ensure a unique URL in the SMS
            $hashids = new Hashids(env('HASHIDS_SALT', 'your-default-salt'), 8);
            $appointment->hash = $hashids->encode($appointment->id, now()->timestamp);
            $appointment->saveQuietly(); // Use saveQuietly to avoid an infinite loop of updated events
            $appointment->refresh(); // Ensure the model has the latest hash before dispatching

            SendAppointmentModificationSms::dispatch($appointment->customer, $appointment, $appointment->salon);
        }
    }
}
