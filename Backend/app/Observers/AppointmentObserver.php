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
        // Generate a unique hash after the appointment has been created and has an ID
        $hashids = new Hashids(env('HASHIDS_SALT', 'your-default-salt'), 8);
        $appointment->hash = $hashids->encode($appointment->id);
        $appointment->saveQuietly(); // Use saveQuietly to avoid triggering the updated event

        // Update staff statistics
        if ($appointment->staff) {
            $appointment->staff->updateStatistics();
        }

        // Only create activity log if we have an authenticated user
        if (Auth::id()) {
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
    }

    public function updated(Appointment $appointment)
    {
        if ($appointment->isDirty('status') || $appointment->isDirty('staff_id') || $appointment->isDirty('total_price')) {
            // Update statistics for current staff
            if ($appointment->staff) {
                $appointment->staff->updateStatistics();
            }

            // If staff_id changed, update statistics for old staff too
            if ($appointment->isDirty('staff_id')) {
                $oldStaffId = $appointment->getOriginal('staff_id');
                if ($oldStaffId && $oldStaffId != $appointment->staff_id) {
                    $oldStaff = \App\Models\Staff::find($oldStaffId);
                    if ($oldStaff) {
                        $oldStaff->updateStatistics();
                    }
                }
            }

            if ($appointment->isDirty('status')) {
                $activityType = $appointment->status === 'canceled' ? 'cancelled' : 'updated';
                $customerName = optional($appointment->customer)->name ?? 'N/A';
                $description = "Appointment for customer {$customerName} was {$activityType}.";

                // Only create activity log if we have an authenticated user
                if (Auth::id()) {
                    ActivityLog::create([
                        'user_id' => Auth::id(),
                        'salon_id' => $appointment->salon_id,
                        'activity_type' => $activityType,
                        'description' => $description,
                        'loggable_id' => $appointment->id,
                        'loggable_type' => Appointment::class,
                    ]);
                }
            }
        }

        // Send modification SMS only when date or time changes
        if ($appointment->isDirty('appointment_date') || $appointment->isDirty('start_time')) {
            SendAppointmentModificationSms::dispatch($appointment->customer, $appointment, $appointment->salon);
        }
    }

    public function deleted(Appointment $appointment)
    {
        // Update staff statistics
        if ($appointment->staff) {
            $appointment->staff->updateStatistics();
        }

        // Only create activity log if we have an authenticated user
        if (Auth::id()) {
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

    public function restored(Appointment $appointment)
    {
        // Update staff statistics when appointment is restored
        if ($appointment->staff) {
            $appointment->staff->updateStatistics();
        }
    }
}
