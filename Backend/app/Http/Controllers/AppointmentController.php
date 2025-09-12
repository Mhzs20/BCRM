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
use App\Jobs\SendAppointmentConfirmationSms;
use App\Jobs\SendAppointmentModificationSms;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Hashids\Hashids;
use Illuminate\Pagination\LengthAwarePaginator;  
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
        return AppointmentResource::collection($appointments);
    }
    public function store(StoreAppointmentRequest $request, $salon_id)
    {
        $validatedData = $request->validated();
        if (isset($validatedData['appointment_date'])) {
            try {
                $jalaliDate = Jalalian::fromFormat('Y-m-d', str_replace('/', '-', $validatedData['appointment_date']));
                $carbonDate = $jalaliDate->toCarbon('Asia/Tehran')->startOfDay(); // Explicitly set timezone and start of day
                $validatedData['appointment_date'] = $carbonDate->format('Y-m-d');
            } catch (JalaliException $e) {
                Log::error('Jalali date conversion error in store method: ' . $e->getMessage());
                return response()->json(['message' => 'فرمت تاریخ شمسی نامعتبر است.'], 422);
            }
        }
        $activeServicesCount = \App\Models\Service::where('salon_id', $salon_id)
            ->whereIn('id', $validatedData['service_ids'])
            ->where('is_active', true)
            ->count();
        if ($activeServicesCount !== count($validatedData['service_ids'])) {
            return response()->json(['message' => 'یکی از سرویس‌های انتخاب شده غیر فعال است و امکان ثبت نوبت وجود ندارد.'], 422);
        }
        $customer = null;
        DB::beginTransaction();
        try {
            if (!empty($validatedData['customer_id'])) {
                $customer = Customer::where('salon_id', $salon_id)
                    ->whereNull('deleted_at')
                    ->findOrFail($validatedData['customer_id']);
            } elseif (isset($validatedData['new_customer']['name']) && isset($validatedData['new_customer']['phone_number'])) {
                $customer = Customer::withTrashed()
                    ->where('salon_id', $salon_id)
                    ->where('phone_number', $validatedData['new_customer']['phone_number'])
                    ->first();

                if ($customer) {
                    // If customer was soft-deleted, restore them
                    if ($customer->trashed()) {
                        $customer->restore();
                    }
                    // Update their name if it has changed
                    $customer->name = $validatedData['new_customer']['name'];
                    if (isset($validatedData['new_customer']['email'])) {
                        $customer->email = $validatedData['new_customer']['email'];
                    }
                    $customer->save();
                } else {
                    // If customer does not exist at all, create a new one
                    $customerData = [
                        'salon_id' => $salon_id,
                        'name' => $validatedData['new_customer']['name'],
                        'phone_number' => $validatedData['new_customer']['phone_number'],
                    ];
                    if (isset($validatedData['new_customer']['email'])) {
                        $customerData['email'] = $validatedData['new_customer']['email'];
                    }
                    $customer = Customer::create($customerData);
                }
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
                    'internal_note' => $validatedData['internal_notes'] ?? null, // Map internal_notes to internal_note
                    'deposit_required' => $validatedData['deposit_required'] ?? false,
                    'deposit_paid' => $validatedData['deposit_paid'] ?? false,
                    'deposit_amount' => $validatedData['deposit_amount'] ?? 0,
                    'deposit_payment_method' => $validatedData['deposit_payment_method'] ?? null,
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

            // Dispatch the job to send SMS in the background
            SendAppointmentConfirmationSms::dispatch($customer, $appointment, $appointment->salon);
            
            $message = 'نوبت با موفقیت ثبت شد. پیامک تایید به زودی ارسال خواهد شد.';

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

        return new AppointmentResource($appointment);
    }

    public function update(UpdateAppointmentRequest $request, Salon $salon, Appointment $appointment)
    {
        if ($appointment->salon_id != $salon->id) {
            return response()->json(['message' => 'نوبت یافت نشد.'], 404);
        }

        $validatedData = $request->validated();

        // Always convert Jalali date to Gregorian before any processing
        if (isset($validatedData['appointment_date'])) {
            try {
                $jalaliDate = Jalalian::fromFormat('Y-m-d', str_replace('/', '-', $validatedData['appointment_date']));
                $carbonDate = $jalaliDate->toCarbon('Asia/Tehran')->startOfDay(); // Explicitly set timezone and start of day
                $validatedData['appointment_date'] = $carbonDate->format('Y-m-d');
            } catch (JalaliException $e) {
                Log::error('Jalali date conversion error in update method: ' . $e->getMessage());
                return response()->json(['message' => 'فرمت تاریخ شمسی نامعتبر است.'], 422);
            }
        }

        $recalculationFields = ['service_ids', 'staff_id', 'appointment_date', 'start_time'];
        $needsRecalculation = !empty(array_intersect(array_keys($request->validated()), $recalculationFields));

        DB::beginTransaction();

        try {
                $oldServiceIds = $appointment->services->pluck('id')->toArray();
                $oldStaffId = $appointment->staff_id;
                $oldDate = $appointment->appointment_date->format('Y-m-d');
                $oldStartTime = Carbon::parse($appointment->start_time)->format('H:i');

                $newServiceIds = $validatedData['service_ids'] ?? $oldServiceIds;
                $newStaffId = $validatedData['staff_id'] ?? $oldStaffId;
                $newDate = $validatedData['appointment_date'] ?? $oldDate;
                $newStartTime = $validatedData['start_time'] ?? $oldStartTime;
                $newTotalDuration = $validatedData['total_duration'] ?? $appointment->total_duration;

                $appointmentModified = (
                    $newServiceIds !== $oldServiceIds ||
                    $newDate != $oldDate ||
                    $newStartTime != $oldStartTime
                );

                if ($needsRecalculation) {
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
                        $newTotalDuration,
                        $validatedData['notes'] ?? $appointment->notes
                    );

                    $appointment->update($appointmentDetails['appointment_data']);
                    $appointment->services()->sync($appointmentDetails['service_pivot_data']);

                    // ارسال پیامک بروزرسانی فقط اگر واقعا نوبت تغییر کرده باشد
                    if ($appointmentModified) {
                        $customer = $appointment->customer;
                        if ($customer) {
                            \App\Jobs\SendAppointmentModificationSms::dispatch($customer, $appointment, $salon);
                        }
                    }
                }

            $updateData = Arr::except($validatedData, ['service_ids', 'internal_notes']);

            if (!empty($updateData)) {
                $appointment->update($updateData);
            }

            // Manually update internal_note if present in validatedData
            if (isset($validatedData['internal_notes'])) {
                $appointment->internal_note = $validatedData['internal_notes'];
                $appointment->save();
            }

            DB::commit();

            $message = 'نوبت با موفقیت به‌روزرسانی شد.';

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

        if ($appointment->status === 'done') {
            return response()->json(['message' => 'امکان لغو نوبت انجام شده وجود ندارد.'], 403);
        }

        $customer = $appointment->customer;
        $salon = Salon::with('user')->findOrFail($salon_id);

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

            return response()->json(['data' => AppointmentResource::collection($appointments)]);
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
            return response()->json(['data' => AppointmentResource::collection($appointments)]);
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
     *
     * @param GetMonthlyAppointmentsRequest $request
     * @param int $salon_id
     * @return \Illuminate\Http\JsonResponse
     */
public function getMonthlyAppointmentsCount($salon_id, $year, $month)
{
    $validator = Validator::make(['year' => $year, 'month' => $month], [
        'year' => 'required|integer|min:1300|max:1500',
        'month' => 'required|integer|min:1|max:12',
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => 'سال یا ماه نامعتبر است.', 'errors' => $validator->errors()], 422);
    }

    try {
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
     *
     * @param Request $request
     * @param int $salon_id
     * @param int $year سال شمسی
     * @param int $month ماه شمسی
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAppointmentsByMonth(Request $request, $salon_id, $year, $month)
    {

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

            return AppointmentResource::collection($appointments);

        } catch (\Exception $e) {
            Log::error('Error fetching appointments by month: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در دریافت نوبت‌ها.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
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

            if ($request->has('end_date')) {
                $gregorianEndDate = Jalalian::fromFormat('Y-m-d', $request->input('end_date'))->toCarbon()->endOfDay();
                $query->where('appointment_date', '<=', $gregorianEndDate);
            }

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('staff_id')) {
                $query->where('staff_id', $request->input('staff_id'));
            }

            $appointments = $query
                ->with(['customer:id,name,phone_number', 'staff:id,full_name', 'services'])
                ->orderBy('appointment_date', 'desc')
                ->orderBy('start_time', 'desc')
                ->paginate($request->input('per_page', 15));

            return AppointmentResource::collection($appointments);

        } catch (\Exception $e) {
            Log::error('Error in getAppointments:', ['error' => $e]);
            return response()->json(['message' => 'خطا در دریافت نوبت‌ها.', 'error' => $e->getMessage()], 500);
        }
    }

    public function sendReminderSms(Request $request, Salon $salon, Appointment $appointment)
    {
        if ($appointment->salon_id != $salon->id) {
            return response()->json(['message' => 'نوبت یافت نشد.'], 404);
        }

        if ($appointment->status === 'done') {
            return response()->json(['message' => 'برای نوبت انجام شده امکان ارسال پیامک یادآوری وجود ندارد.'], 403);
        }

        try {
            $customer = $appointment->customer;
            if (!$customer) {
                return response()->json(['message' => 'مشتری این نوبت یافت نشد.'], 404);
            }

            $smsResult = $this->smsService->sendManualAppointmentReminder($customer, $appointment, $salon);

            if (isset($smsResult['status']) && $smsResult['status'] === 'success') {
                // We don't update reminder_sms_sent_at here to allow the cron job to run
                return response()->json(['message' => 'پیامک یادآوری دستی با موفقیت ارسال شد.']);
            } else {
                return response()->json(['message' => 'خطا در ارسال پیامک یادآوری دستی.', 'details' => $smsResult['message'] ?? ''], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error sending manual reminder SMS for appointment ' . $appointment->id . ': ' . $e->getMessage());
            return response()->json(['message' => 'خطای سیستمی در ارسال پیامک یادآوری دستی رخ داد.'], 500);
        }
    }

    public function sendModificationSms(Request $request, Salon $salon, Appointment $appointment)
    {
        if ($appointment->salon_id != $salon->id) {
            return response()->json(['message' => 'نوبت یافت نشد.'], 404);
        }

        try {
            $customer = $appointment->customer;
            if (!$customer) {
                return response()->json(['message' => 'مشتری این نوبت یافت نشد.'], 404);
            }

            $smsResult = $this->smsService->sendAppointmentModification($customer, $appointment, $salon);

            if (isset($smsResult['status']) && $smsResult['status'] === 'success') {
                return response()->json(['message' => 'پیامک اصلاح نوبت با موفقیت ارسال شد.']);
            } else {
                return response()->json(['message' => 'خطا در ارسال پیامک اصلاح نوبت.', 'details' => $smsResult['message'] ?? ''], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error sending modification SMS for appointment ' . $appointment->id . ': ' . $e->getMessage());
            return response()->json(['message' => 'خطای سیستمی در ارسال پیامک اصلاح نوبت رخ داد.'], 500);
        }
    }
}
