<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Salon;
use Illuminate\Http\Request;
use App\Jobs\SendAppointmentConfirmationSms;
use Illuminate\Support\Facades\DB;
use App\Services\SmsService;
use Morilog\Jalali\Jalalian;

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

        $query = Appointment::where('salon_id', $salonId)
            ->where('source', 'online_booking')
            ->with(['customer', 'staff', 'services']);

        // Status Filter
        if ($status) {
            if ($status === 'pending') {
                $query->where('status', 'pending_confirmation');
            } elseif ($status === 'confirmed') {
                $query->whereIn('status', ['confirmed', 'completed']);
            } elseif ($status === 'canceled') {
                $query->where('status', 'canceled');
            }
        } else {
            // Default to pending_confirmation if no status is provided (preserving existing behavior)
            $query->where('status', 'pending_confirmation');
        }

        // Jalali Date Filter
        if ($jalaliDate) {
            try {
                // Replace / with - for consistency
                $jalaliDate = str_replace('/', '-', $jalaliDate);
                $date = Jalalian::fromFormat('Y-m-d', $jalaliDate)->toCarbon()->format('Y-m-d');
                $query->whereDate('appointment_date', $date);
            } catch (\Exception $e) {
                // Ignore invalid date format
            }
        }

        $appointments = $query->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $appointments
        ]);
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
