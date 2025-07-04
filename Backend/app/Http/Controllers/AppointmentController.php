<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Salon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Requests\GetAvailableSlotsRequest;
use App\Http\Requests\CalendarQueryRequest;
use App\Services\AppointmentBookingService;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Arr;

class AppointmentController extends Controller
{
    protected AppointmentBookingService $appointmentBookingService;
    public function __construct(AppointmentBookingService $appointmentBookingService)
    {
        $this->appointmentBookingService = $appointmentBookingService;
    }
    public function index(Request $request, $salon_id)
    {
        $appointments = Appointment::where('salon_id', $salon_id)
            ->with(['customer:id,name,phone_number', 'staff:id,full_name', 'services:id,name,duration_minutes'])
            ->orderBy($request->input('sort_by', 'appointment_date'), $request->input('sort_direction', 'desc'))
            ->orderBy('start_time', $request->input('sort_direction', 'desc'))
            ->paginate($request->input('per_page', 15));
        return response()->json($appointments);
    }
    public function store(StoreAppointmentRequest $request, $salon_id)
    {
        $validatedData = $request->validated();
        if (isset($validatedData['appointment_date'])) {
            $validatedData['appointment_date'] = Jalalian::fromFormat('Y-m-d', str_replace('/', '-', $validatedData['appointment_date']))
                ->toCarbon()
                ->format('Y-m-d');
        }
        $customer = null;
        DB::beginTransaction();
        try {
            if (!empty($validatedData['customer_id'])) {
                $customer = Customer::where('salon_id', $salon_id)
                    ->whereNull('deleted_at')
                    ->findOrFail($validatedData['customer_id']);
            } elseif (isset($validatedData['new_customer']['name']) && isset($validatedData['new_customer']['phone_number'])) {
                $customerData = [
                    'salon_id' => $salon_id,
                    'name' => $validatedData['new_customer']['name'],
                    'phone_number' => $validatedData['new_customer']['phone_number'],
                ];
                if(isset($validatedData['new_customer']['email'])) {
                    $customerData['email'] = $validatedData['new_customer']['email'];
                }
                $customer = Customer::create($customerData);
            } else {
                DB::rollBack();
                return response()->json(['message' => 'اطلاعات مشتری برای ثبت نوبت ناقص است.'], 422);
            }
            $appointmentDetails = $this->appointmentBookingService->prepareAppointmentData(
                $salon_id,
                $customer->id,
                $validatedData['staff_id'],
                $validatedData['service_ids'],
                $validatedData['appointment_date'],
                $validatedData['start_time'],
                $validatedData['notes'] ?? null
            );
            $finalAppointmentData = array_merge(
                $appointmentDetails['appointment_data'],
                [
                    'status' => $validatedData['status'] ?? 'confirmed',
                    'deposit_required' => $validatedData['deposit_required'] ?? false,
                    'deposit_paid' => $validatedData['deposit_paid'] ?? false,
                ]
            );
            if (!$this->appointmentBookingService->isSlotStillAvailable(
                $salon_id,
                $validatedData['staff_id'],
                $validatedData['service_ids'],
                $validatedData['appointment_date'],
                $validatedData['start_time']
            )) {
                DB::rollBack();
                return response()->json(['message' => 'متاسفانه این زمان به تازگی پر شده است. لطفا زمان دیگری انتخاب کنید.'], 409);
            }
            $appointment = Appointment::create($finalAppointmentData);
            if (!empty($appointmentDetails['service_pivot_data'])) {
                $appointment->services()->attach($appointmentDetails['service_pivot_data']);
            }
            DB::commit();
            $appointment->load(['customer', 'staff', 'services']);
            return response()->json(['message' => 'نوبت با موفقیت ثبت شد.', 'data' => $appointment], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Appointment store failed - Model not found: ' . $e->getMessage());
            return response()->json(['message' => 'اطلاعات مرجع (مانند مشتری یا پرسنل) یافت نشد.'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Appointment store failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در ثبت نوبت رخ داد.', 'error_details_for_debug' => $e->getMessage()], 500);
        }
    }

    public function show($salon_id, Appointment $appointment)
    {
        if ($appointment->salon_id != $salon_id) {
            return response()->json(['message' => 'نوبت یافت نشد.'], 404);
        }

        $appointment->load(['customer', 'staff', 'services']);

        return response()->json($appointment);
    }

    public function update(UpdateAppointmentRequest $request, Salon $salon, Appointment $appointment)
{
    if ($appointment->salon_id != $salon->id) {
        return response()->json(['message' => 'نوبت یافت نشد.'], 404);
    }

    $validatedData = $request->validated();
    $recalculationFields = ['service_ids', 'staff_id', 'appointment_date', 'start_time'];
    $needsRecalculation = !empty(array_intersect(array_keys($validatedData), $recalculationFields));

    DB::beginTransaction();

    try {
        if ($needsRecalculation) {
            $newServiceIds = $validatedData['service_ids'] ?? $appointment->services->pluck('id')->toArray();
            $newStaffId = $validatedData['staff_id'] ?? $appointment->staff_id;

            $newDate = isset($validatedData['appointment_date'])
                ? Jalalian::fromFormat('Y-m-d', str_replace('/', '-', $validatedData['appointment_date']))->toCarbon()->format('Y-m-d')
                : $appointment->appointment_date->format('Y-m-d');

            $newStartTime = $validatedData['start_time'] ?? Carbon::parse($appointment->start_time)->format('H:i');

            if (!$this->appointmentBookingService->isSlotStillAvailable(
                $salon->id,
                $newStaffId,
                $newServiceIds,
                $newDate,
                $newStartTime,
                $appointment->id
            )) {
                DB::rollBack();
                return response()->json(['message' => 'زمان انتخابی جدید با نوبت دیگری تداخل دارد.'], 409);
            }

            $appointmentDetails = $this->appointmentBookingService->prepareAppointmentData(
                $salon->id,
                $appointment->customer_id,
                $newStaffId,
                $newServiceIds,
                $newDate,
                $newStartTime,
                $validatedData['notes'] ?? $appointment->notes
            );

            $appointment->update($appointmentDetails['appointment_data']);
            $appointment->services()->sync($appointmentDetails['service_pivot_data']);
        }

        $simpleUpdateData = Arr::except($validatedData, $recalculationFields);
        if (!empty($simpleUpdateData)) {
            $appointment->update($simpleUpdateData);
        }

        DB::commit();

        $appointment->refresh()->load(['customer', 'staff', 'services']);
        return response()->json(['message' => 'نوبت با موفقیت به‌روزرسانی شد.', 'data' => $appointment]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Appointment update failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json(['message' => 'خطا در به‌روزرسانی نوبت.', 'error' => $e->getMessage()], 500);
    }
}

    public function destroy($salon_id, Appointment $appointment)
    {
        if ($appointment->salon_id != $salon_id) {
            return response()->json(['message' => 'نوبت یافت نشد.'], 404);
        }
        $appointment->delete();
        return response()->json(['message' => 'نوبت با موفقیت حذف شد.'], 200);
    }
    public function getAvailableSlots(GetAvailableSlotsRequest $request, $salon_id)
    {
        $validated = $request->validated();
        if (isset($validated['date'])) {
            $validated['date'] = Jalalian::fromFormat('Y-m-d', str_replace('/', '-', $validated['date']))
                ->toCarbon()
                ->format('Y-m-d');
        }
        try {
            $availableSlots = $this->appointmentBookingService->findAvailableSlots(
                $salon_id,
                $validated['staff_id'],
                $validated['service_ids'],
                $validated['date']
            );
            return response()->json(['data' => $availableSlots]);
        } catch (\Exception $e) {
            Log::error('Get available slots failed: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در دریافت ساعات خالی: ' . $e->getMessage()], 500);
        }
    }
    public function getCalendarAppointments(CalendarQueryRequest $request, $salon_id)
    {
        $validated = $request->validated();
        if (isset($validated['start_date'])) {
            $validated['start_date'] = Jalalian::fromFormat('Y-m-d', str_replace('/', '-', $validated['start_date']))->toCarbon();
        }
        if (isset($validated['end_date'])) {
            $validated['end_date'] = Jalalian::fromFormat('Y-m-d', str_replace('/', '-', $validated['end_date']))->toCarbon();
        }
        $query = Appointment::where('salon_id', $salon_id)
            ->whereBetween('appointment_date', [$validated['start_date'], $validated['end_date']])
            ->with(['customer:id,name,phone_number', 'staff:id,full_name', 'services:id,name,duration_minutes']);
        if (!empty($validated['staff_id'])) {
            $query->where('staff_id', $validated['staff_id']);
        }
        $appointments = $query->orderBy('appointment_date')->orderBy('start_time')->get();
        return response()->json(['data' => $appointments]);
    }
}
