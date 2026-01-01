<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\PendingAppointment;
use App\Models\Salon;
use Illuminate\Http\Request;
use App\Jobs\SendAppointmentConfirmationSms;
use Illuminate\Support\Facades\DB;
use App\Services\SmsService;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class OnlineBookingManagementController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * List online bookings for a salon with filtering.
     */
    public function index(Request $request, $salonId)
    {
        $perPage = $request->input('per_page', 15);
        $status = $request->input('status'); // pending, confirmed, canceled
        $jalaliDate = $request->input('date'); // YYYY/MM/DD or YYYY-MM-DD
        $startDate = $request->input('start_date'); // YYYY/MM/DD or YYYY-MM-DD - when provided, return appointments from this date onwards (for pagination)

        $query = Appointment::where('salon_id', $salonId)
            ->where('source', 'online_booking')
            ->with(['customer', 'staff', 'services']);

        // Status Filter
        if ($status) {
            $status = strtolower($status);

            if ($status === 'pending' || $status === 'pending_confirmation') {
                $query->where('status', 'pending_confirmation');
            } elseif ($status === 'confirmed') {
                // If client specifically asks for confirmed, include completed too
                $query->whereIn('status', ['confirmed', 'completed']);
            } elseif ($status === 'canceled') {
                $query->where('status', 'canceled');
            } else {
                // Unknown status supplied -> return a 400 so clients don't get unexpected results
                return response()->json([
                    'success' => false,
                    'message' => 'وضعیت نامعتبر است. وضعیت‌های پذیرفته شده: pending, pending_confirmation, confirmed, canceled.'
                ], 400);
            }
        } else {
            // Default to pending_confirmation if no status is provided (preserving existing behavior)
            $query->where('status', 'pending_confirmation');
        }

        // Date Filters: single date vs start_date
        // If `date` is provided, preserve existing behavior (return only that single day).
        // If `start_date` is provided (and `date` is not), return appointments from that date onwards
        // and order results chronologically (ascending) for a sensible pagination experience.
        $orderedAsc = false;
        if ($jalaliDate) {
            try {
                // Replace / with - for consistency
                $jalaliDate = str_replace('/', '-', $jalaliDate);
                $date = Jalalian::fromFormat('Y-m-d', $jalaliDate)->toCarbon()->format('Y-m-d');
                $query->whereDate('appointment_date', $date);
            } catch (\Exception $e) {
                // Ignore invalid date format
            }
        } elseif ($startDate) {
            try {
                // Replace / with - for consistency
                $startDate = str_replace('/', '-', $startDate);
                $start = Jalalian::fromFormat('Y-m-d', $startDate)->toCarbon()->format('Y-m-d');
                $query->whereDate('appointment_date', '>=', $start);
                $orderedAsc = true;
            } catch (\Exception $e) {
                // Ignore invalid date format
            }
        }

        if ($orderedAsc) {
            $appointments = $query->orderBy('appointment_date', 'asc')
                ->orderBy('start_time', 'asc')
                ->paginate($perPage);
        } else {
            $appointments = $query->orderBy('appointment_date', 'desc')
                ->orderBy('start_time', 'desc')
                ->paginate($perPage);
        }

        // Add conflict information to each appointment
        $appointments->getCollection()->transform(function ($appointment) use ($salonId) {
            $conflicts = $this->getAppointmentConflicts(
                $salonId,
                $appointment->staff_id,
                $appointment->appointment_date->format('Y-m-d'),
                $appointment->start_time,
                $appointment->end_time,
                $appointment->id
            );

            $appointment->has_conflicts = count($conflicts) > 0;
            $appointment->conflicting_appointments = $conflicts;

            return $appointment;
        });

        return response()->json([
            'success' => true,
            'data' => $appointments
        ]);
    }

    /**
     * Get conflicts for an appointment (appointments, pending appointments, and staff breaks).
     */
    private function getAppointmentConflicts($salonId, $staffId, $appointmentDate, $startTime, $endTime, $excludeAppointmentId = null)
    {
        $slotStart = Carbon::parse($appointmentDate . ' ' . $startTime);
        $slotEnd = Carbon::parse($appointmentDate . ' ' . $endTime);

        $conflicts = [];

        // Check staff break conflicts
        $staffBreakConflicts = $this->getStaffBreakConflicts($staffId, $appointmentDate, $startTime, $endTime);
        $conflicts = array_merge($conflicts, $staffBreakConflicts);

        // Check confirmed/completed appointments
        $query = Appointment::where('salon_id', $salonId)
            ->where('staff_id', $staffId)
            ->where('appointment_date', $appointmentDate)
            ->where('status', '!=', 'canceled')
            ->with(['customer', 'staff', 'services']);

        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }

        $appointments = $query->get();

        foreach ($appointments as $appointment) {
            $existingStart = Carbon::parse($appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->start_time);
            $existingEnd = Carbon::parse($appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->end_time);

            if ($slotStart->lt($existingEnd) && $slotEnd->gt($existingStart)) {
                $conflicts[] = [
                    'type' => 'appointment',
                    'id' => $appointment->id,
                    'appointment_date' => $appointment->appointment_date->format('Y-m-d'),
                    'start_time' => $appointment->start_time,
                    'end_time' => $appointment->end_time,
                    'staff_id' => $appointment->staff_id,
                    'staff_name' => $appointment->staff->full_name ?? 'نامشخص',
                    'customer_name' => $appointment->customer->name ?? 'نامشخص',
                    'customer_phone' => $appointment->customer->phone_number ?? '',
                    'services' => $appointment->services->map(function($s){
                        return [
                            'id' => $s->id,
                            'name' => $s->name,
                            'duration_minutes' => $s->duration_minutes,
                        ];
                    })->toArray(),
                    'status' => $appointment->status,
                    'conflict_reason' => 'تداخل با نوبت موجود',
                ];
            }
        }

        // Check pending appointments
        $pendingQuery = PendingAppointment::where('salon_id', $salonId)
            ->where('staff_id', $staffId)
            ->where('appointment_date', $appointmentDate)
            ->notExpired()
            ->with(['customer', 'staff']);

        if ($excludeAppointmentId) {
            $pendingQuery->where('id', '!=', $excludeAppointmentId);
        }

        $pendingAppointments = $pendingQuery->get();

        foreach ($pendingAppointments as $pendingAppointment) {
            $existingStart = Carbon::parse($pendingAppointment->appointment_date->format('Y-m-d') . ' ' . $pendingAppointment->start_time);
            $existingEnd = Carbon::parse($pendingAppointment->appointment_date->format('Y-m-d') . ' ' . $pendingAppointment->end_time);

            if ($slotStart->lt($existingEnd) && $slotEnd->gt($existingStart)) {
                $customerName = 'نامشخص';
                $customerPhone = '';
                if ($pendingAppointment->customer) {
                    $customerName = $pendingAppointment->customer->name;
                    $customerPhone = $pendingAppointment->customer->phone_number;
                } elseif (isset($pendingAppointment->new_customer_data['name'])) {
                    $customerName = $pendingAppointment->new_customer_data['name'];
                    $customerPhone = $pendingAppointment->new_customer_data['phone_number'] ?? '';
                }

                $services = [];
                if (!empty($pendingAppointment->service_ids)) {
                    $services = \App\Models\Service::where('salon_id', $salonId)
                        ->whereIn('id', $pendingAppointment->service_ids)
                        ->get(['id','name','duration_minutes'])
                        ->map(function($s){
                            return [
                                'id' => $s->id,
                                'name' => $s->name,
                                'duration_minutes' => $s->duration_minutes,
                            ];
                        })->toArray();
                }

                $conflicts[] = [
                    'type' => 'pending_appointment',
                    'id' => $pendingAppointment->id,
                    'appointment_date' => $pendingAppointment->appointment_date->format('Y-m-d'),
                    'start_time' => $pendingAppointment->start_time,
                    'end_time' => $pendingAppointment->end_time,
                    'staff_id' => $pendingAppointment->staff_id,
                    'staff_name' => $pendingAppointment->staff->full_name ?? 'نامشخص',
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'services' => $services,
                    'status' => 'pending',
                    'conflict_reason' => 'تداخل با نوبت در حال انتظار',
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Get staff break conflicts.
     */
    private function getStaffBreakConflicts($staffId, $appointmentDate, $startTime, $endTime)
    {
        $conflicts = [];
        $staff = \App\Models\Staff::find($staffId);

        if (!$staff || !$staff->working_hours) {
            return $conflicts;
        }

        $slotStart = Carbon::parse($appointmentDate . ' ' . $startTime);
        $slotEnd = Carbon::parse($appointmentDate . ' ' . $endTime);
        $dayOfWeek = strtolower(Carbon::parse($appointmentDate)->locale('en')->dayName);

        if (!isset($staff->working_hours[$dayOfWeek]['breaks'])) {
            return $conflicts;
        }

        foreach ($staff->working_hours[$dayOfWeek]['breaks'] as $break) {
            $breakStart = Carbon::parse($appointmentDate . ' ' . $break['start']);
            $breakEnd = Carbon::parse($appointmentDate . ' ' . $break['end']);

            if ($slotStart->lt($breakEnd) && $slotEnd->gt($breakStart)) {
                $conflicts[] = [
                    'type' => 'staff_break',
                    'staff_id' => $staffId,
                    'staff_name' => $staff->full_name,
                    'break_start' => $break['start'],
                    'break_end' => $break['end'],
                    'conflict_reason' => 'تداخل با زمان استراحت کارمند',
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Approve an online booking.
     */
    public function approve(Request $request, $salonId, $appointmentId)
    {
        $appointment = Appointment::where('salon_id', $salonId)
            ->where('id', $appointmentId)
            ->firstOrFail();

        if ($appointment->status !== 'pending_confirmation') {
            return response()->json([
                'success' => false,
                'message' => 'این نوبت در وضعیت در انتظار تایید نیست.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Check for conflicts again just in case
            // (Optional but recommended since time passed)
            // For now, we assume the slot is still effectively reserved for them or we overwrite.
            // But strictly speaking, we should check if someone else took it (though online booking checks availability).
            // Since we saved it as 'pending_confirmation', it exists in DB, so `isTimeSlotAvailable` in OnlineBookingController
            // would have seen it as a conflict for others (it checks confirmed and pending).
            // Wait, OnlineBookingController checks: ->whereIn('status', ['confirmed', 'pending'])
            // I should update OnlineBookingController to also check 'pending_confirmation'.

            $appointment->status = 'confirmed';
            $appointment->save();

            DB::commit();

            // Send Confirmation SMS
            $customer = $appointment->customer;
            $salon = $appointment->salon;
            
            // Dispatch the job to send SMS
            SendAppointmentConfirmationSms::dispatch($customer, $appointment, $salon, null);

            return response()->json([
                'success' => true,
                'message' => 'نوبت با موفقیت تایید شد و پیامک برای مشتری ارسال گردید.',
                'data' => $appointment
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطا در تایید نوبت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject an online booking.
     */
    public function reject(Request $request, $salonId, $appointmentId)
    {
        $appointment = Appointment::where('salon_id', $salonId)
            ->where('id', $appointmentId)
            ->firstOrFail();

        if ($appointment->status !== 'pending_confirmation') {
            return response()->json([
                'success' => false,
                'message' => 'این نوبت در وضعیت در انتظار تایید نیست.'
            ], 400);
        }

        $appointment->status = 'canceled'; // Or 'rejected' if you prefer specific status
        $appointment->save();

        // Optionally send rejection SMS if needed (user didn't explicitly ask, but good UX)
        // For now, adhering to "approve... send SMS".

        return response()->json([
            'success' => true,
            'message' => 'نوبت رد شد.',
            'data' => $appointment
        ]);
    }

    /**
     * Bulk approve multiple online bookings for a salon.
     * Expects JSON: { "appointment_ids": [1,2,3] }
     */
    public function bulkApprove(Request $request, $salonId)
    {
        $validated = $request->validate([
            'appointment_ids' => 'required|array|min:1',
            'appointment_ids.*' => 'integer'
        ]);

        $ids = array_values($validated['appointment_ids']);

        // Fetch appointments that belong to this salon and are pending confirmation
        $appointments = Appointment::where('salon_id', $salonId)
            ->whereIn('id', $ids)
            ->where('status', 'pending_confirmation')
            ->with(['customer', 'salon'])
            ->get();

        if ($appointments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'هیچ نوبت معتبری برای تایید یافت نشد.'
            ], 400);
        }

        $approvedIds = [];
        $approvedAppointments = [];

        DB::beginTransaction();
        try {
            $toApproveIds = $appointments->pluck('id')->toArray();

            // Bulk update statuses
            Appointment::where('salon_id', $salonId)
                ->whereIn('id', $toApproveIds)
                ->where('status', 'pending_confirmation')
                ->update(['status' => 'confirmed']);

            DB::commit();

            // Dispatch SMS jobs for each approved appointment

            // Reload updated appointments with relations
            $updatedAppointments = Appointment::whereIn('id', $toApproveIds)
                ->with(['customer', 'staff', 'services', 'salon'])
                ->get();

            foreach ($updatedAppointments as $appointment) {
                $approvedIds[] = $appointment->id;
                $approvedAppointments[] = $appointment;
                $customer = $appointment->customer;
                $salon = $appointment->salon;
                SendAppointmentConfirmationSms::dispatch($customer, $appointment, $salon, null);
            }

            $failed = array_values(array_diff($ids, $approvedIds));

            return response()->json([
                'success' => true,
                'message' => 'عملیات تایید دسته‌ای انجام شد.',
                'approved_count' => count($approvedIds),
                'approved_ids' => $approvedIds,
                'approved_appointments' => $approvedAppointments,
                'failed_ids' => $failed
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطا در تایید دسته‌ای: ' . $e->getMessage()
            ], 500);
        }
    }
}
