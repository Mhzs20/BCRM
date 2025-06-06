<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;

class AppointmentBookingService
{
    const SLOT_INTERVAL_MINUTES = 15;

    public function findAvailableSlots(int $salonId, int $staffId, array $serviceIds, string $date): array
    {
        $staff = Staff::with('schedules')->where('salon_id', $salonId)->findOrFail($staffId);
        $services = Service::where('salon_id', $salonId)
            ->whereIn('id', $serviceIds)
            ->where('is_active', true)
            ->get();

        if ($services->isEmpty()) {
            throw new \Exception('حداقل یک خدمت معتبر و فعال باید انتخاب شود.');
        }

        $totalDurationMinutes = $services->sum('duration_minutes');
        if ($totalDurationMinutes <= 0) {
            throw new \Exception('مجموع مدت زمان خدمات باید بیشتر از صفر دقیقه باشد.');
        }

        $carbonDate = Carbon::parse($date)->startOfDay();
        $workingHours = $this->getStaffWorkingHoursForDay($staff, $carbonDate->dayOfWeek);

        if (!$workingHours || !$workingHours['active'] || !$workingHours['start'] || !$workingHours['end']) {
            return [];
        }

        $workStartTime = Carbon::parse($date . ' ' . $workingHours['start']);
        $workEndTime = Carbon::parse($date . ' ' . $workingHours['end']);

        $existingAppointments = Appointment::where('salon_id', $salonId)
            ->where('staff_id', $staffId)
            ->where('appointment_date', $date)
            ->orderBy('start_time')
            ->get();

        $availableSlots = [];
        $slotInterval = self::SLOT_INTERVAL_MINUTES;
        $periodEndTime = $workEndTime->copy()->subMinutes($totalDurationMinutes);

        if ($workStartTime->gt($periodEndTime)) {
            return [];
        }

        $period = new CarbonPeriod($workStartTime, "{$slotInterval} minutes", $periodEndTime);

        foreach ($period as $slotStartTime) {
            if ($slotStartTime->lt($workEndTime)) {
                $slotEndTime = $slotStartTime->copy()->addMinutes($totalDurationMinutes);
                if ($slotEndTime->gt($workEndTime)) {
                    continue;
                }

                $isOverlapping = false;
                foreach ($existingAppointments as $existingAppointment) {
                    $existingStart = Carbon::parse($date . ' ' . $existingAppointment->start_time);
                    $existingEnd = Carbon::parse($date . ' ' . $existingAppointment->end_time);
                    if ($slotStartTime->lt($existingEnd) && $slotEndTime->gt($existingStart)) {
                        $isOverlapping = true;
                        break;
                    }
                }
                if (!$isOverlapping) {
                    $availableSlots[] = $slotStartTime->format('H:i');
                }
            }
        }
        return array_values(array_unique($availableSlots));
    }

    protected function getStaffWorkingHoursForDay(Staff $staff, int $dayOfWeekCarbon): ?array
    {
        $schedule = $staff->schedules()
            ->where('day_of_week', $dayOfWeekCarbon)
            ->first();

        if ($schedule && $schedule->is_active && $schedule->start_time && $schedule->end_time) {
            return [
                'start' => $schedule->start_time,
                'end' => $schedule->end_time,
                'active' => true,
            ];
        }
        return ['start' => null, 'end' => null, 'active' => false];
    }

    public function prepareAppointmentData(int $salonId, int $customerId, int $staffId, array $serviceIds, string $appointmentDate, string $startTime, ?string $notes): array
    {
        $services = Service::where('salon_id', $salonId)->whereIn('id', $serviceIds)->where('is_active', true)->get();
        if ($services->isEmpty()) {
            throw new \Exception('سرویس‌های انتخاب شده معتبر یا فعال نیستند.');
        }

        $totalDuration = $services->sum('duration_minutes');
        $totalPrice = $services->sum('price');
        $carbonStartTime = Carbon::parse($appointmentDate . ' ' . $startTime);
        $endTime = $carbonStartTime->copy()->addMinutes($totalDuration)->format('H:i:s');

        $appointmentData = [
            'salon_id' => $salonId,
            'customer_id' => $customerId,
            'staff_id' => $staffId,
            'appointment_date' => $appointmentDate,
            'start_time' => $carbonStartTime->format('H:i:s'),
            'end_time' => $endTime,
            'status' => 'confirmed',
            'notes' => $notes,
            'total_price' => $totalPrice,
        ];

        $servicePivotData = [];
        foreach ($services as $service) {
            $servicePivotData[$service->id] = [
                'price_at_booking' => $service->price,
                'duration_at_booking' => $service->duration_minutes,
            ];
        }
        return ['appointment_data' => $appointmentData, 'service_pivot_data' => $servicePivotData];
    }

    public function isSlotStillAvailable(int $salonId, int $staffId, array $serviceIds, string $date, string $startTime, ?int $ignoreAppointmentId = null): bool
    {
        $services = Service::where('salon_id', $salonId)->whereIn('id', $serviceIds)->where('is_active', true)->get();
        if ($services->isEmpty()) {
            return false;
        }
        $totalDurationMinutes = $services->sum('duration_minutes');
        $slotStartTime = Carbon::parse($date . ' ' . $startTime);
        $slotEndTime = $slotStartTime->copy()->addMinutes($totalDurationMinutes);

        $query = Appointment::where('salon_id', $salonId)
            ->where('staff_id', $staffId)
            ->where('appointment_date', $date)
            ->where(function ($q) use ($slotStartTime, $slotEndTime) {
                $q->where('start_time', '<', $slotEndTime->format('H:i:s'))
                    ->where('end_time', '>', $slotStartTime->format('H:i:s'));
            });

        if ($ignoreAppointmentId !== null) {
            $query->where('id', '!=', $ignoreAppointmentId);
        }

        $conflictingAppointments = $query->count();
        return $conflictingAppointments === 0;
    }
}
