<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Jobs\SendAppointmentModificationSms;
use App\Jobs\SendSatisfactionSurveySms;
use App\Models\ActivityLog;
use Hashids\Hashids;
use Illuminate\Support\Facades\Auth;

class AppointmentObserver
{
    public function created(Appointment $appointment)
    {
        $customerName = optional($appointment->customer)->name ?? 'N/A';
        ActivityLog::create([
            'user_id' => Auth::id(),
            'salon_id' => $appointment->salon_id,
            'activity_type' => 'created',
            'description' => "New appointment created for customer: {$customerName}",
            'loggable_id' => $appointment->id,
            'loggable_type' => Appointment::class,
        ]);
    }

    public function updated(Appointment $appointment)
    {
        if ($appointment->isDirty('status')) {
            $activityType = $appointment->status === 'cancelled' ? 'cancelled' : 'updated';
            $customerName = optional($appointment->customer)->name ?? 'N/A';
            $description = "Appointment for customer {$customerName} was {$activityType}.";

            ActivityLog::create([
                'user_id' => Auth::id(),
                'salon_id' => $appointment->salon_id,
                'activity_type' => $activityType,
                'description' => $description,
                'loggable_id' => $appointment->id,
                'loggable_type' => Appointment::class,
            ]);
        }

        if ($appointment->isDirty('status') && $appointment->status === 'done') {
            SendSatisfactionSurveySms::dispatch($appointment);
        } elseif ($appointment->isDirty('status') || $appointment->isDirty('appointment_date') || $appointment->isDirty('start_time')) {
            $hashids = new Hashids(env('HASHIDS_SALT', 'your-default-salt'), 8);
            $appointment->hash = $hashids->encode($appointment->id, now()->timestamp);
            $appointment->saveQuietly();
            $appointment->refresh();

            SendAppointmentModificationSms::dispatch($appointment->customer, $appointment, $appointment->salon);
        }
    }

    public function deleted(Appointment $appointment)
    {
        $customerName = optional($appointment->customer)->name ?? 'N/A';
        ActivityLog::create([
            'user_id' => Auth::id(),
            'salon_id' => $appointment->salon_id,
            'activity_type' => 'deleted',
            'description' => "Appointment for customer {$customerName} was deleted.",
            'loggable_id' => $appointment->id,
            'loggable_type' => Appointment::class,
        ]);
    }
}
