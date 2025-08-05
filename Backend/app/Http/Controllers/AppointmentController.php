<?php

namespace App\Http\Controllers;

use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Salon;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Requests\GetAvailableSlotsRequest;
use App\Http\Requests\CalendarQueryRequest;
use App\Http\Requests\GetMonthlyAppointmentsRequest;
use App\Services\AppointmentBookingService;
use App\Services\SmsService;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Hashids\Hashids;
use Illuminate\Pagination\LengthAwarePaginator; // <-- این خط اضافه می‌شود
use Illuminate\Support\Facades\Validator;
use Morilog\Jalali\JalaliException; 

class AppointmentController extends Controller
{
    protected AppointmentBookingService $appointmentBookingService;
    protected SmsService $smsService;

    public function __construct(AppointmentBookingService $appointmentBookingService, SmsService $smsService)
    {
        $this->appointmentBookingService = $appointmentBookingService;
        $this->smsService = $smsService;
    }
    public function index(Request $request, $salon_id)
    {
        $appointments = Appointment::where('salon_id', $salon_id)
            ->with(['customer:id,name,phone_number', 'staff:id,full_name', 'services:id,name']) // Removed duration_minutes
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
                $validatedData['service_ids'], // service_ids are still needed for pivot data
                $validatedData['appointment_date'],
                $validatedData['start_time'],
                $validatedData['total_duration'], // Add total_duration
                $validatedData['notes'] ?? null
            );
            // Fetch settings for the specific salon.
            $salonSettings = Setting::where('salon_id', $salon_id)->pluck('value', 'key');

            $finalAppointmentData = array_merge(
                $appointmentDetails['appointment_data'],
                [
                    'total_price' => $validatedData['total_price'] ?? $appointmentDetails['appointment_data']['total_price'],
                    'status' => $validatedData['status'] ?? 'confirmed',
                    'deposit_required' => $validatedData['deposit_required'] ?? false,
                    'deposit_paid' => $validatedData['deposit_paid'] ?? false,
                    'reminder_time' => $validatedData['reminder_time'] ?? null,
                    'send_reminder_sms' => $validatedData['send_reminder_sms'] ?? filter_var($salonSettings->get('enable_reminder_sms_globally', true), FILTER_VALIDATE_BOOLEAN),
                    'send_satisfaction_sms' => $validatedData['send_satisfaction_sms'] ?? filter_var($salonSettings->get('enable_satisfaction_sms_globally', true), FILTER_VALIDATE_BOOLEAN),
                ]
            );
            if (!$this->appointmentBookingService->isSlotStillAvailable(
                $salon_id,
                $validatedData['staff_id'],
                $validatedData['service_ids'], // service_ids are still needed for validation
                $validatedData['total_duration'], // Add total_duration
                $validatedData['appointment_date'],
                $validatedData['start_time']
            )) {
                DB::rollBack();
                return response()->json(['message' => 'متاسفانه این زمان به تازگی پر شده است. لطفا زمان دیگری انتخاب کنید.'], 409);
            }
            $appointment = new Appointment($finalAppointmentData);
            $appointment->save();
            $hashids = new Hashids(env('HASHIDS_SALT', 'your-default-salt'), 8);
            $appointment->hash = $hashids->encode($appointment->id);
            $appointment->save();
            if (!empty($appointmentDetails['service_pivot_data'])) {
                $appointment->services()->attach($appointmentDetails['service_pivot_data']);
            }
            
            DB::commit();

            $smsResult = $this->smsService->sendAppointmentConfirmation($customer, $appointment, $appointment->salon);
            
            $message = 'نوبت با موفقیت ثبت شد.';
            if (isset($smsResult['status']) && $smsResult['status'] === 'error') {
                $message .= ' اما پیامک ارسال نشد: ' . $smsResult['message'];
            }

            $appointment->load(['customer', 'staff', 'services']);

            return response()->json(['message' => $message, 'data' => $appointment], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('خطا در ثبت نوبت - مدل یافت نشد: ' . $e->getMessage());
            return response()->json(['message' => 'اطلاعات مرجع (مانند مشتری یا پرسنل) یافت نشد.'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('خطا در ثبت نوبت: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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

        // Always convert Jalali date to Gregorian before any processing
        if (isset($validatedData['appointment_date'])) {
            $validatedData['appointment_date'] = Jalalian::fromFormat('Y-m-d', str_replace('/', '-', $validatedData['appointment_date']))
                ->toCarbon()
                ->format('Y-m-d');
        }

        $recalculationFields = ['service_ids', 'staff_id', 'appointment_date', 'start_time'];
        $needsRecalculation = !empty(array_intersect(array_keys($request->validated()), $recalculationFields));

        DB::beginTransaction();

        try {
            if ($needsRecalculation) {
                $newServiceIds = $validatedData['service_ids'] ?? $appointment->services->pluck('id')->toArray();
                $newStaffId = $validatedData['staff_id'] ?? $appointment->staff_id;
                $newDate = $validatedData['appointment_date'] ?? $appointment->appointment_date->format('Y-m-d');
                $newStartTime = $validatedData['start_time'] ?? Carbon::parse($appointment->start_time)->format('H:i');

                $newTotalDuration = $validatedData['total_duration'] ?? $appointment->total_duration; // Get total_duration for update

                if (!$this->appointmentBookingService->isSlotStillAvailable($salon->id, $newStaffId, $newServiceIds, $newTotalDuration, $newDate, $newStartTime, $appointment->id)) {
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
                    $newTotalDuration, // Pass total_duration
                    $validatedData['notes'] ?? $appointment->notes
                );

                $appointment->update($appointmentDetails['appointment_data']);
                $appointment->services()->sync($appointmentDetails['service_pivot_data']);
            }

            $updateData = Arr::except($validatedData, ['service_ids']);

            if (!empty($updateData)) {
                $appointment->update($updateData);
            }

            DB::commit();

            $smsResult = $this->smsService->sendAppointmentModification($appointment->customer, $appointment, $salon);

            $message = 'نوبت با موفقیت به‌روزرسانی شد.';
            if (isset($smsResult['status']) && $smsResult['status'] === 'error') {
                $message .= ' اما پیامک ارسال نشد: ' . $smsResult['message'];
            }

            $appointment->refresh()->load(['customer', 'staff', 'services']);
            return response()->json(['message' => $message, 'data' => new AppointmentResource($appointment)]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('خطا در به‌روزرسانی نوبت: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در به‌روزرسانی نوبت.', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($salon_id, Appointment $appointment)
    {
        if ($appointment->salon_id != $salon_id) {
            return response()->json(['message' => 'نوبت یافت نشد.'], 404);
        }

        $customer = $appointment->customer;
        $salon = Salon::findOrFail($salon_id);
        
        // Update the status to 'canceled' instead of deleting
        $appointment->update(['status' => 'canceled']);

        $smsResult = $this->smsService->sendAppointmentCancellation($customer, $appointment, $salon);

        $message = 'نوبت با موفقیت لغو شد.';
        if (isset($smsResult['status']) && $smsResult['status'] === 'error') {
            $message .= ' اما پیامک لغو نوبت ارسال نشد: ' . $smsResult['message'];
        }

        return response()->json(['message' => $message], 200);
    }
    public function getAvailableSlots(GetAvailableSlotsRequest $request, $salon_id)
    {
        $validated = $request->validated();

        try {
            $query = Appointment::where('salon_id', $salon_id)
                ->where('appointment_date', $validated['date'])
                ->with(['customer:id,name,phone_number', 'staff:id,full_name', 'services:id,name']);

            if ($validated['staff_id'] != -1) {
                $query->where('staff_id', $validated['staff_id']);
            }

            if (isset($validated['service_ids']) && !in_array(-1, $validated['service_ids'])) {
                $query->whereHas('services', function ($q) use ($validated) {
                    $q->whereIn('service_id', $validated['service_ids']);
                });
            }

            $appointments = $query->orderBy('start_time')->get();

            return response()->json(['data' => $appointments]);
        } catch (\Exception $e) {
            Log::error('خطا در دریافت نوبت‌ها: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در دریافت نوبت‌ها: ' . $e->getMessage()], 500);
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
            ->with(['customer:id,name,phone_number', 'staff:id,full_name', 'services:id,name']); // Removed duration_minutes
        if (!empty($validated['staff_id'])) {
            $query->where('staff_id', $validated['staff_id']);
        }
        $appointments = $query->orderBy('appointment_date')->orderBy('start_time')->get();
            return response()->json(['data' => $appointments]);
    }
    /**
     * یک تابع کمکی خصوصی برای محاسبه دقیق بازه تاریخ یک ماه شمسی.
     * این تابع از تکرار کد جلوگیری کرده و صحت محاسبات را تضمین می‌کند.
     * @param int $year سال شمسی
     * @param int $month ماه شمسی
     * @return array [Carbon $startDate, Carbon $endDate]
     */
    private function getJalaliMonthDateRange(int $year, int $month): array
    {
        $paddedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);

        // تاریخ شروع ماه شمسی
        $jalaliStartDate = Jalalian::fromFormat('Y-m-d', "$year-$paddedMonth-01");
        $startDate = $jalaliStartDate->toCarbon()->startOfDay();

        // محاسبه دقیق تاریخ پایان ماه شمسی
        $daysInMonth = $jalaliStartDate->getMonthDays();
        $jalaliEndDate = Jalalian::fromFormat('Y-m-d', "$year-$paddedMonth-$daysInMonth");
        $endDate = $jalaliEndDate->toCarbon()->endOfDay();

        return [$startDate, $endDate];
    }
    /**
     * دریافت تعداد نوبت‌ها در هر روز از یک ماه شمسی مشخص.
     * این متد برای نمایش یک تقویم با تعداد نوبت‌ها در هر روز مناسب است.
     *
     * @param GetMonthlyAppointmentsRequest $request
     * @param int $salon_id
     * @return \Illuminate\Http\JsonResponse
     */
public function getMonthlyAppointmentsCount($salon_id, $year, $month) // پارامترها از URL خوانده می‌شوند
{
    // ولیدیشن ورودی‌ها به صورت دستی اضافه شد
    $validator = Validator::make(['year' => $year, 'month' => $month], [
        'year' => 'required|integer|min:1300|max:1500',
        'month' => 'required|integer|min:1|max:12',
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => 'سال یا ماه نامعتبر است.', 'errors' => $validator->errors()], 422);
    }

    try {
        // استفاده از تابع کمکی برای دریافت بازه زمانی صحیح و دقیق شمسی
        [$startDate, $endDate] = $this->getJalaliMonthDateRange($year, $month);

        $appointmentsCount = Appointment::where('salon_id', $salon_id)
            ->whereBetween('appointment_date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(appointment_date) as gregorian_date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('gregorian_date')
            ->orderBy('gregorian_date')
            ->get()
            ->map(function ($item) {
                $carbonDate = Carbon::parse($item->gregorian_date);
                return [
                    'date' => $item->gregorian_date, // فرمت Y-m-d
                    'jalali_date' => Jalalian::fromCarbon($carbonDate)->format('Y/m/d'),
                    'count' => $item->count,
                ];
            });

        return response()->json(['data' => $appointmentsCount]);
    } catch (\Exception $e) {
        Log::error('Error fetching monthly appointments count: ' . $e->getMessage());
        return response()->json(['message' => 'خطا در دریافت تعداد نوبت‌های ماهانه.', 'error' => $e->getMessage()], 500);
    }
}

    /**
     * دریافت لیست کامل نوبت‌های یک ماه شمسی مشخص با صفحه‌بندی (Pagination).
     * این متد جایگزین getYearlyAppointments شده و از یک API استاندارد پیروی می‌کند.
     * مسیر پیشنهادی: GET /api/salons/{salon_id}/appointments-by-month/{year}/{month}
     *
     * @param Request $request
     * @param int $salon_id
     * @param int $year سال شمسی
     * @param int $month ماه شمسی
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAppointmentsByMonth(Request $request, $salon_id, $year, $month)
    {
        // ولیدیشن ورودی‌ها
        $validator = Validator::make(['year' => $year, 'month' => $month], [
            'year' => 'required|integer|min:1300|max:1500',
            'month' => 'required|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'سال یا ماه نامعتبر است.', 'errors' => $validator->errors()], 422);
        }

        try {
            [$startDate, $endDate] = $this->getJalaliMonthDateRange($year, $month);

            $appointments = Appointment::where('salon_id', $salon_id)
                ->whereBetween('appointment_date', [$startDate, $endDate])
                ->with(['customer:id,name,phone_number', 'staff:id,full_name', 'services:id,name']) // Removed duration_minutes
                ->orderBy('appointment_date')
                ->orderBy('start_time')
                ->paginate($request->input('per_page', 15)); 

            $appointments->through(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'hash' => $appointment->hash,
                    'status' => $appointment->status,
                    'appointment_date' => $appointment->appointment_date->format('Y-m-d'),
                    'jalali_appointment_date' => Jalalian::fromCarbon($appointment->appointment_date)->format('Y/m/d'),
                    'start_time' => Carbon::parse($appointment->start_time)->format('H:i'),
                    'end_time' => Carbon::parse($appointment->end_time)->format('H:i'),
                    'total_price' => $appointment->total_price,
                    'notes' => $appointment->notes,
                    'customer' => $appointment->customer,
                    'staff' => $appointment->staff,
                    'services' => $appointment->services,
                ];
            });

            return response()->json($appointments);

        } catch (\Exception $e) {
            Log::error('Error fetching appointments by month: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در دریافت نوبت‌ها.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * دریافت لیست کامل نوبت‌ها از یک روز مشخص تا همان روز در ماه بعدی.
     * مسیر پیشنهادی: GET /api/salons/{salon_id}/appointments-by-month/{year}/{month}/{day}
     *
     * @param Request $request
     * @param int $salon_id
     * @param int $year سال شمسی
     * @param int $month ماه شمسی
     * @param int $day روز شمسی
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAppointments(Request $request, $salon_id)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'sometimes|required|date_format:Y-m-d',
            'end_date' => 'sometimes|required|date_format:Y-m-d|after_or_equal:start_date',
            'status' => 'sometimes|required|string',
            'staff_id' => 'sometimes|required|integer|exists:staff,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'پارامترهای ارسالی نامعتبر است.', 'errors' => $validator->errors()], 422);
        }

        try {
            $query = Appointment::where('salon_id', $salon_id);

            if ($request->has('start_date')) {
                $gregorianStartDate = Jalalian::fromFormat('Y-m-d', $request->input('start_date'))->toCarbon()->startOfDay();
                $query->where('appointment_date', '>=', $gregorianStartDate);
            }

            // اعمال فیلتر تاریخ پایان به صورت داینامیک
            if ($request->has('end_date')) {
                $gregorianEndDate = Jalalian::fromFormat('Y-m-d', $request->input('end_date'))->toCarbon()->endOfDay();
                $query->where('appointment_date', '<=', $gregorianEndDate);
            }

            // اعمال فیلتر وضعیت
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            // اعمال فیلتر آرایشگر
            if ($request->has('staff_id')) {
                $query->where('staff_id', $request->input('staff_id'));
            }

            $appointments = $query
                ->with(['customer:id,name,phone_number', 'staff:id,full_name', 'services'])
                ->orderBy('appointment_date', 'desc')
                ->orderBy('start_time', 'desc')
                ->paginate($request->input('per_page', 15));

            // ✅ 2. بلوک through() به طور کامل حذف شده است
            // ✅ 3. از ریسورس برای بازگرداندن پاسخ استفاده می‌شود
            return AppointmentResource::collection($appointments);

        } catch (\Exception $e) {
            Log::error('Error in getAppointments:', ['error' => $e]);
            return response()->json(['message' => 'خطا در دریافت نوبت‌ها.', 'error' => $e->getMessage()], 500);
        }
    }
}
