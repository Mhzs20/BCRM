<?php

namespace App\Http\Controllers;

use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\PendingAppointment;
use App\Models\Customer;
use App\Models\Salon;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\StoreOldAppointmentRequest;
use App\Http\Requests\PrepareAppointmentRequest;
use App\Http\Requests\SubmitAppointmentRequest;
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
use App\Http\Controllers\Traits\StaffBreakTrait;


class AppointmentController extends Controller
{
    use StaffBreakTrait;
    /**
      * @param int $salonId
     * @param int $staffId
     * @param string $appointmentDate YYYY-MM-DD
     * @param string $startTime HH:MM
     * @param string $endTime HH:MM
     * @param int|null $excludeAppointmentId ID  
     * @return array 
     */
    private function getAppointmentConflicts($salonId, $staffId, $appointmentDate, $startTime, $endTime, $excludeAppointmentId = null)
    {
        $slotStart = Carbon::parse($appointmentDate . ' ' . $startTime);
        $slotEnd = Carbon::parse($appointmentDate . ' ' . $endTime);

        $conflicts = [];

         $staffBreakConflicts = $this->getStaffBreakConflicts($staffId, $appointmentDate, $startTime, $endTime);
        $conflicts = array_merge($conflicts, $staffBreakConflicts);

         $query = Appointment::where('salon_id', $salonId)
            ->where('appointment_date', $appointmentDate)
            ->where('status', '!=', 'canceled')
            ->with(['customer', 'services']);

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
                    'customer_name' => $appointment->customer->name ?? 'نامشخص',
                    'customer_phone' => $appointment->customer->phone_number ?? '',
                    'services' => $appointment->services->pluck('name')->toArray(),
                    'status' => $appointment->status,
                    'conflict_reason' => 'تداخل با نوبت موجود',
                ];
            }
        }

         $pendingQuery = PendingAppointment::where('salon_id', $salonId)
            ->where('appointment_date', $appointmentDate)
            ->notExpired()
            ->with(['customer']); // Load customer relationship

        if ($excludeAppointmentId) {
            $pendingQuery->where('id', '!=', $excludeAppointmentId);
        }

        $pendingAppointments = $pendingQuery->get();

        foreach ($pendingAppointments as $pendingAppointment) {
            $existingStart = Carbon::parse($pendingAppointment->appointment_date->format('Y-m-d') . ' ' . $pendingAppointment->start_time);
            $existingEnd = Carbon::parse($pendingAppointment->appointment_date->format('Y-m-d') . ' ' . $pendingAppointment->end_time);

            if ($slotStart->lt($existingEnd) && $slotEnd->gt($existingStart)) {
                // Get customer info
                $customerName = 'نامشخص';
                $customerPhone = '';
                if ($pendingAppointment->customer) {
                    $customerName = $pendingAppointment->customer->name;
                    $customerPhone = $pendingAppointment->customer->phone_number;
                } elseif (isset($pendingAppointment->new_customer_data['name'])) {
                    $customerName = $pendingAppointment->new_customer_data['name'];
                    $customerPhone = $pendingAppointment->new_customer_data['phone_number'] ?? '';
                }

                // Get services info
                $services = [];
                if (!empty($pendingAppointment->service_ids)) {
                    $services = \App\Models\Service::where('salon_id', $salonId)
                        ->whereIn('id', $pendingAppointment->service_ids)
                        ->pluck('name')
                        ->toArray();
                }

                $conflicts[] = [
                    'type' => 'appointment',
                    'id' => $pendingAppointment->id,
                    'appointment_date' => $pendingAppointment->appointment_date->format('Y-m-d'),
                    'start_time' => $pendingAppointment->start_time,
                    'end_time' => $pendingAppointment->end_time,
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

        $startTime = $validatedData['start_time'];
        $totalDuration = $validatedData['total_duration'] ?? 0;
        $endTime = Carbon::parse($startTime)->addMinutes($totalDuration)->format('H:i');
        $conflictingItems = $this->getAppointmentConflicts($salon_id, $validatedData['staff_id'], $validatedData['appointment_date'], $startTime, $endTime);
        $hasConflicts = !empty($conflictingItems);

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
                    'send_confirmation_sms' => $validatedData['send_confirmation_sms'] ?? filter_var($salonSettings->get('enable_confirmation_sms_globally', true), FILTER_VALIDATE_BOOLEAN),
                    'confirmation_sms_template_id' => $validatedData['confirmation_sms_template_id'] ?? null,
                    'reminder_sms_template_id' => $validatedData['reminder_sms_template_id'] ?? null,
                ]
            );

            $appointment = new Appointment($finalAppointmentData);
            $appointment->save();
            $hashids = new Hashids(env('HASHIDS_SALT', 'your-default-salt'), 8);
            $appointment->hash = $hashids->encode($appointment->id);
            $appointment->save();
            if (!empty($appointmentDetails['service_pivot_data'])) {
                $appointment->services()->attach($appointmentDetails['service_pivot_data']);
            }
            
            DB::commit();

             $appointment->load(['customer', 'staff', 'services']);

             $templateId = $finalAppointmentData['confirmation_sms_template_id'] ?? null;

             SendAppointmentConfirmationSms::dispatch($customer, $appointment, $appointment->salon, $templateId);
            
            $message = 'نوبت با موفقیت ثبت شد. پیامک تایید به زودی ارسال خواهد شد.';

            return response()->json([
                'message' => $message, 
                'data' => new AppointmentResource($appointment),
                'has_conflicts' => $hasConflicts,
                'conflicting_appointments' => $conflictingItems
            ], 201);
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

        DB::beginTransaction();

        try {
           
                $newEndTime = Carbon::parse($newStartTime)->addMinutes($newTotalDuration)->format('H:i');
                $conflictingItems = $this->getAppointmentConflicts($salon->id, $newStaffId, $newDate, $newStartTime, $newEndTime, $appointment->id);
                $hasConflicts = !empty($conflictingItems);
                if ($needsRecalculation) {
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

                    if ($appointmentModified) {
                        $customer = $appointment->customer;
                        if ($customer) {
                            \App\Jobs\SendAppointmentModificationSms::dispatch($customer, $appointment, $salon);
                        }
                    }
                }

            $updateData = Arr::except($validatedData, ['service_ids', 'internal_notes']);

            $oldStatus = $appointment->status;
            $newStatus = $updateData['status'] ?? $oldStatus;

            if (!empty($updateData)) {
                $appointment->update($updateData);
            }

            $smsSent = false;
            if ($newStatus === 'confirmed' && $oldStatus !== 'confirmed') {
                $customer = $appointment->customer;
                if ($customer) {
                    $smsService = $this->smsService;
                    $smsResult = $smsService->sendAppointmentConfirmation($customer, $appointment, $salon);
                    if (isset($smsResult['status']) && $smsResult['status'] === 'success') {
                        $smsSent = 'confirmation';
                    }
                }
            } elseif ($appointmentModified && !($newStatus === 'confirmed' && $oldStatus !== 'confirmed')) {
                $customer = $appointment->customer;
                if ($customer) {
                    \App\Jobs\SendAppointmentModificationSms::dispatch($customer, $appointment, $salon);
                    $smsSent = 'modification';
                }
            }

            // Manually update internal_note if present in validatedData
            if (isset($validatedData['internal_notes'])) {
                $appointment->internal_note = $validatedData['internal_notes'];
                $appointment->save();
            }

            DB::commit();

            $message = 'نوبت با موفقیت به‌روزرسانی شد.';
            if ($smsSent === 'confirmation') {
                $message .= ' پیامک تایید نوبت ارسال شد.';
            } elseif ($smsSent === 'modification') {
                $message .= ' پیامک تغییر نوبت ارسال شد.';
            }

            $appointment->refresh()->load(['customer', 'staff', 'services']);
            return response()->json([
                'message' => $message, 
                'sms_sent' => $smsSent,
                'data' => new AppointmentResource($appointment),
                'has_conflicts' => $hasConflicts,
                'conflicting_appointments' => $conflictingItems
            ]);

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

        if ($appointment->status === 'completed') {
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
                ->with(['customer:id,name,phone_number', 'staff:id,full_name', 'services:id,name']);

            // فیلتر بازه تاریخی
            if (!empty($validated['start_date']) && !empty($validated['end_date'])) {
                $query->whereBetween('appointment_date', [$validated['start_date'], $validated['end_date']]);
            } elseif (!empty($validated['start_date'])) {
                $query->where('appointment_date', '>=', $validated['start_date']);
            } elseif (!empty($validated['end_date'])) {
                $query->where('appointment_date', '<=', $validated['end_date']);
            }

            if ($validated['staff_id'] != -1) {
                $query->where('staff_id', $validated['staff_id']);
            }

            if (isset($validated['service_ids']) && !in_array(-1, $validated['service_ids'])) {
                $query->whereHas('services', function ($q) use ($validated) {
                    $q->whereIn('services.id', $validated['service_ids']);
                });
            }

            $appointments = $query->orderBy('appointment_date')->orderBy('start_time')->get();

            return response()->json(['data' => AppointmentResource::collection($appointments)]);
        } catch (\Exception $e) {
                Log::error('خطا در دریافت نوبت‌ها: ' . $e->getMessage());
                return response()->json(['message' => 'خطا در دریافت نوبت‌ها: ' . $e->getMessage()], 500);
            }

        }

        /**
          */
        public function getAvailableSlotsPaginated(GetAvailableSlotsRequest $request, $salon_id)
        {
            $validated = $request->validated();
            try {
                $query = Appointment::where('salon_id', $salon_id)
                    ->with(['customer:id,name,phone_number', 'staff:id,full_name', 'services:id,name']);

                // فیلتر بازه تاریخی
                if (!empty($validated['start_date']) && !empty($validated['end_date'])) {
                    $query->whereBetween('appointment_date', [$validated['start_date'], $validated['end_date']]);
                } elseif (!empty($validated['start_date'])) {
                    $query->where('appointment_date', '>=', $validated['start_date']);
                } elseif (!empty($validated['end_date'])) {
                    $query->where('appointment_date', '<=', $validated['end_date']);
                }

                if ($validated['staff_id'] != -1) {
                    $query->where('staff_id', $validated['staff_id']);
                }

                if (isset($validated['service_ids']) && !in_array(-1, $validated['service_ids'])) {
                    $query->whereHas('services', function ($q) use ($validated) {
                        $q->whereIn('services.id', $validated['service_ids']);
                    });
                }

                $perPage = $request->input('per_page', 15);
                $appointments = $query->orderBy('appointment_date')->orderBy('start_time')->paginate($perPage);

                return response()->json(['data' => AppointmentResource::collection($appointments)]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
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
      * @param int $year سال شمسی
     * @param int $month ماه شمسی
     * @return array [Carbon $startDate, Carbon $endDate]
     */
    private function getJalaliMonthDateRange(int $year, int $month): array
    {
        $paddedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);

         $jalaliStartDate = Jalalian::fromFormat('Y-m-d', "$year-$paddedMonth-01");
        $startDate = $jalaliStartDate->toCarbon()->startOfDay();

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
        return response()->json(['message' => 'خطا در دریافت آمار نوبت‌ها.', 'error' => $e->getMessage()], 500);
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
            Log::error('Error fetching appointments: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در دریافت نوبت‌ها.', 'error' => $e->getMessage()], 500);
        }

        if ($appointment->status === 'completed') {
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

    /**
     * Get SMS templates for appointments
     */
    public function smsTemplates(Request $request, $salon_id)
    {
        $eventType = $request->query('event_type');
        
        $query = \App\Models\SalonSmsTemplate::where(function($q) use ($salon_id) {
            $q->where('salon_id', $salon_id)
              ->orWhereNull('salon_id'); // Include global templates
        })
        ->where('is_active', true);
        
        if ($eventType) {
            $query->where('event_type', $eventType);
        }
        
        $templates = $query->orderBy('salon_id', 'asc') // Global templates first
                          ->orderBy('event_type')
                          ->get();
        
        return response()->json([
            'templates' => $templates,
            'message' => 'تمپلیت‌های SMS با موفقیت دریافت شدند'
        ]);
    }

    /**
     * Prepare appointment - validates data and checks for conflicts
     */
    public function prepareAppointment(PrepareAppointmentRequest $request, $salon_id)
    {
        $validatedData = $request->validated();
        
        // Convert Jalali date to Gregorian
        if (isset($validatedData['appointment_date'])) {
            try {
                $jalaliDate = Jalalian::fromFormat('Y-m-d', str_replace('/', '-', $validatedData['appointment_date']));
                $carbonDate = $jalaliDate->toCarbon('UTC')->startOfDay();
                $validatedData['appointment_date'] = $carbonDate->format('Y-m-d');
            } catch (JalaliException $e) {
                Log::error('Jalali date conversion error in prepare method: ' . $e->getMessage());
                return response()->json(['message' => 'فرمت تاریخ شمسی نامعتبر است.'], 422);
            }
        }

        // Check if services are active
        $activeServicesCount = \App\Models\Service::where('salon_id', $salon_id)
            ->whereIn('id', $validatedData['service_ids'])
            ->where('is_active', true)
            ->count();
        
        if ($activeServicesCount !== count($validatedData['service_ids'])) {
            return response()->json(['message' => 'یکی از سرویس‌های انتخاب شده غیر فعال است.'], 422);
        }

        $customer = null;
        $newCustomerData = null;

        DB::beginTransaction();
        try {
            // Handle customer
            if (!empty($validatedData['customer_id'])) {
                $customer = Customer::where('salon_id', $salon_id)
                    ->whereNull('deleted_at')
                    ->findOrFail($validatedData['customer_id']);
            } elseif (isset($validatedData['new_customer']['name']) && isset($validatedData['new_customer']['phone_number'])) {
                // Check if customer already exists
                $existingCustomer = Customer::withTrashed()
                    ->where('salon_id', $salon_id)
                    ->where('phone_number', $validatedData['new_customer']['phone_number'])
                    ->first();

                if ($existingCustomer) {
                    $customer = $existingCustomer;
                    if ($customer->trashed()) {
                        $customer->restore();
                    }
                } else {
                    // Store new customer data to create later
                    $newCustomerData = $validatedData['new_customer'];
                }
            } else {
                DB::rollBack();
                return response()->json(['message' => 'اطلاعات مشتری برای ثبت نوبت ناقص است.'], 422);
            }

            // Prepare appointment data
            $appointmentDetails = $this->appointmentBookingService->prepareAppointmentData(
                $salon_id,
                $customer ? $customer->id : 0, // Always pass int, never null
                $validatedData['staff_id'],
                $validatedData['service_ids'],
                $validatedData['appointment_date'],
                $validatedData['start_time'],
                $validatedData['total_duration'],
                $validatedData['notes'] ?? null
            );

            // Unified conflict detection logic - check both confirmed appointments and pending appointments
            $slotStart = Carbon::parse($validatedData['appointment_date'] . ' ' . $validatedData['start_time']);
            $slotEnd = $slotStart->copy()->addMinutes($validatedData['total_duration']);
            $endTime = $slotEnd->format('H:i');
            $conflictingAppointments = $this->getAppointmentConflicts($salon_id, $validatedData['staff_id'], $validatedData['appointment_date'], $validatedData['start_time'], $endTime);

            // Separate conflicts into appointment and staff break conflicts
            $appointmentConflicts = array_filter($conflictingAppointments, function($c) {
                return $c['type'] === 'appointment';
            });
            $staffBreakConflicts = array_filter($conflictingAppointments, function($c) {
                return $c['type'] === 'staff_break';
            });

            // Get salon settings
            $salonSettings = Setting::where('salon_id', $salon_id)->pluck('value', 'key');

            // Always create pending appointment, but show conflicts if any
            $pendingData = array_merge(
                $appointmentDetails['appointment_data'],
                [
                    'total_price' => $validatedData['total_price'] ?? $appointmentDetails['appointment_data']['total_price'],
                    'status' => $validatedData['status'] ?? 'pending',
                    'internal_note' => $validatedData['internal_notes'] ?? null,
                    'deposit_required' => $validatedData['deposit_required'] ?? false,
                    'deposit_paid' => $validatedData['deposit_paid'] ?? false,
                    'deposit_amount' => $validatedData['deposit_amount'] ?? 0,
                    'deposit_payment_method' => $validatedData['deposit_payment_method'] ?? null,
                    'reminder_time' => $validatedData['reminder_time'] ?? null,
                    'send_reminder_sms' => $validatedData['send_reminder_sms'] ?? filter_var($salonSettings->get('enable_reminder_sms_globally', true), FILTER_VALIDATE_BOOLEAN),
                    'send_satisfaction_sms' => $validatedData['send_satisfaction_sms'] ?? filter_var($salonSettings->get('enable_satisfaction_sms_globally', true), FILTER_VALIDATE_BOOLEAN),
                    'send_confirmation_sms' => $validatedData['send_confirmation_sms'] ?? filter_var($salonSettings->get('enable_confirmation_sms_globally', true), FILTER_VALIDATE_BOOLEAN),
                    'confirmation_sms_template_id' => $validatedData['confirmation_sms_template_id'] ?? null,
                    'reminder_sms_template_id' => $validatedData['reminder_sms_template_id'] ?? null,
                    'service_ids' => $validatedData['service_ids'],
                    'new_customer_data' => $newCustomerData,
                    'conflicting_appointments' => $conflictingAppointments,
                    'expires_at' => Carbon::now()->addMinutes(1), // 2 minutes to confirm
                ]
            );

            // Remove customer_id if we don't have a customer yet
            if (!$customer) {
                unset($pendingData['customer_id']);
            }

            $pendingAppointment = PendingAppointment::create($pendingData);

            DB::commit();

            // Load services for display
            $services = \App\Models\Service::where('salon_id', $salon_id)
                ->whereIn('id', $validatedData['service_ids'])
                ->get(['id', 'name', 'price']);

            $staff = \App\Models\Staff::where('salon_id', $salon_id)
                ->where('id', $validatedData['staff_id'])
                ->first(['id', 'full_name']);

            // Load salon with relationships for complete response
            $salon = Salon::with(['businessCategory', 'businessSubcategories', 'province', 'city', 'user'])
                ->findOrFail($salon_id);

            $response = [
                'success' => true,
                'pending_appointment_id' => $pendingAppointment->id,
                'expires_in_minutes' => $pendingAppointment->getExpiresInMinutes(),
                'appointment_details' => [
                    'salon_id' => $salon_id,
                    'customer_id' => $customer ? $customer->id : null,
                    'staff_id' => $validatedData['staff_id'],
                    'appointment_date' => $validatedData['appointment_date'],
                    'appointment_date_jalali' => Jalalian::fromCarbon(Carbon::parse($validatedData['appointment_date']))->format('Y/m/d'),
                    'start_time' => $validatedData['start_time'],
                    'end_time' => $appointmentDetails['appointment_data']['end_time'],
                    'total_duration' => $validatedData['total_duration'],
                    'total_price' => $pendingData['total_price'],
                    'status' => $pendingData['status'],
                    'notes' => $validatedData['notes'] ?? null,
                    'internal_note' => $validatedData['internal_notes'] ?? null,
                    'deposit_required' => $pendingData['deposit_required'],
                    'deposit_paid' => $pendingData['deposit_paid'],
                    'deposit_amount' => $pendingData['deposit_amount'],
                    'deposit_payment_method' => $pendingData['deposit_payment_method'],
                    'reminder_time' => $pendingData['reminder_time'],
                    'send_reminder_sms' => $pendingData['send_reminder_sms'],
                    'send_satisfaction_sms' => $pendingData['send_satisfaction_sms'],
                    'send_confirmation_sms' => $pendingData['send_confirmation_sms'],
                    'confirmation_sms_template_id' => $pendingData['confirmation_sms_template_id'],
                    'reminder_sms_template_id' => $pendingData['reminder_sms_template_id'],
                    'staff' => [
                        'id' => $staff->id,
                        'full_name' => $staff->full_name,
                        'phone_number' => $staff->phone_number ?? null,
                        'specialty' => $staff->specialty ?? null,
                        'profile_image' => $staff->profile_image ?? null,
                    ],
                    'customer' => $customer ? [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'phone_number' => $customer->phone_number,
                        'profile_image' => $customer->profile_image ?? null,
                    ] : [
                        'name' => $newCustomerData['name'] ?? null,
                        'phone_number' => $newCustomerData['phone_number'] ?? null,
                    ],
                    'services' => $services->map(function ($service) {
                        return [
                            'id' => $service->id,
                            'name' => $service->name,
                            'price' => $service->price,
                        ];
                    }),
                    'salon' => [
                        'id' => $salon->id,
                        'name' => $salon->name,
                        'address' => $salon->address,
                        'mobile' => $salon->mobile,
                        'image' => $salon->image,
                        'business_category' => $salon->businessCategory ? [
                            'id' => $salon->businessCategory->id,
                            'name' => $salon->businessCategory->name,
                        ] : null,
                    ],
                ],
                'appointment_conflicts' => array_values($appointmentConflicts),
                'staff_break_conflicts' => array_values($staffBreakConflicts),
                'appointment_conflict_message' => !empty($appointmentConflicts) ? 'در زمان انتخابی نوبت دیگری وجود دارد. آیا می‌خواهید ادامه دهید؟' : null,
                'staff_break_conflict_message' => !empty($staffBreakConflicts) ? 'در زمان انتخابی پرسنل مورد نظر در استراحت است. آیا می‌خواهید ادامه دهید؟' : null,
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('خطا در آماده‌سازی نوبت: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'خطا در آماده‌سازی نوبت رخ داد.',
                'error_details_for_debug' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Submit appointment - creates final appointment from pending appointment
     */
    public function submitAppointment(SubmitAppointmentRequest $request, $salon_id)
    {
        $validatedData = $request->validated();
        
        $pendingAppointment = PendingAppointment::notExpired()
            ->where('salon_id', $salon_id)
            ->findOrFail($validatedData['pending_appointment_id']);

        if ($pendingAppointment->isExpired()) {
            return response()->json(['message' => 'نوبت موقت منقضی شده است. لطفا مجدد تلاش کنید.'], 410);
        }

        DB::beginTransaction();
        try {
            $customer = null;

            // Handle customer creation if needed
            if ($pendingAppointment->customer_id) {
                $customer = Customer::findOrFail($pendingAppointment->customer_id);
            } elseif ($pendingAppointment->new_customer_data) {
                $customerData = array_merge(
                    ['salon_id' => $salon_id],
                    $pendingAppointment->new_customer_data
                );
                $customer = Customer::create($customerData);
            } else {
                throw new \Exception('Customer data not found in pending appointment');
            }

            // Create appointment from pending data
            $appointmentData = $pendingAppointment->toArray();
            unset($appointmentData['id'], $appointmentData['service_ids'], $appointmentData['new_customer_data'], 
                  $appointmentData['conflicting_appointments'], $appointmentData['expires_at'], 
                  $appointmentData['created_at'], $appointmentData['updated_at']);
            
            // Ensure appointment_date is correctly set
            $appointmentData['appointment_date'] = $pendingAppointment->appointment_date->format('Y-m-d');
            $appointmentData['customer_id'] = $customer->id;
            $appointmentData['status'] = $pendingAppointment->status;

            $appointment = new Appointment($appointmentData);
            $appointment->save();

            // Generate hash

            // Attach services
            $services = \App\Models\Service::where('salon_id', $salon_id)
                ->whereIn('id', $pendingAppointment->service_ids)
                ->get();

            $servicePivotData = [];
            foreach ($services as $service) {
                $servicePivotData[$service->id] = [
                    'price_at_booking' => $service->price,
                ];
            }

            if (!empty($servicePivotData)) {
                $appointment->services()->attach($servicePivotData);
            }

            // Delete pending appointment
            $pendingAppointment->delete();

            DB::commit();

            // Load relationships
            $appointment->load(['customer', 'staff', 'services']);

            // Send confirmation SMS only if status is confirmed
            if ($appointment->status === 'confirmed') {
                $templateId = $appointment->confirmation_sms_template_id;
                SendAppointmentConfirmationSms::dispatch($customer, $appointment, $appointment->salon, $templateId);
            }

            return response()->json([
                'success' => true,
                'message' => 'نوبت با موفقیت ثبت شد.' . ($appointment->status === 'confirmed' ? ' پیامک تایید به زودی ارسال خواهد شد.' : ''),
                'data' => new AppointmentResource($appointment)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('خطا در ثبت نهایی نوبت: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در ثبت نوبت رخ داد.'], 500);
        }
    }

    /**
     * Store old appointment - creates appointment in past dates with completed status
     */
    public function storeOldAppointment(StoreOldAppointmentRequest $request, $salon_id)
    {
        $validatedData = $request->validated();
        if (isset($validatedData['appointment_date'])) {
            try {
                $jalaliDate = Jalalian::fromFormat('Y-m-d', str_replace('/', '-', $validatedData['appointment_date']));
                $carbonDate = $jalaliDate->toCarbon('Asia/Tehran')->startOfDay(); // Explicitly set timezone and start of day
                $validatedData['appointment_date'] = $carbonDate->format('Y-m-d');
            } catch (JalaliException $e) {
                Log::error('Jalali date conversion error in storeOldAppointment method: ' . $e->getMessage());
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
                    'status' => 'completed', // Default to completed for old appointments
                    'internal_note' => $validatedData['internal_notes'] ?? null, // Map internal_notes to internal_note
                    'deposit_required' => $validatedData['deposit_required'] ?? false,
                    'deposit_paid' => $validatedData['deposit_paid'] ?? false,
                    'deposit_amount' => $validatedData['deposit_amount'] ?? 0,
                    'deposit_payment_method' => $validatedData['deposit_payment_method'] ?? null,
                    'reminder_time' => $validatedData['reminder_time'] ?? null,
                    'send_reminder_sms' => $validatedData['send_reminder_sms'] ?? filter_var($salonSettings->get('enable_reminder_sms_globally', true), FILTER_VALIDATE_BOOLEAN),
                    'send_satisfaction_sms' => $validatedData['send_satisfaction_sms'] ?? filter_var($salonSettings->get('enable_satisfaction_sms_globally', true), FILTER_VALIDATE_BOOLEAN),
                    'send_confirmation_sms' => $validatedData['send_confirmation_sms'] ?? filter_var($salonSettings->get('enable_confirmation_sms_globally', true), FILTER_VALIDATE_BOOLEAN),
                    'confirmation_sms_template_id' => $validatedData['confirmation_sms_template_id'] ?? null,
                    'reminder_sms_template_id' => $validatedData['reminder_sms_template_id'] ?? null,
                ]
            );

            $appointment = new Appointment($finalAppointmentData);
            $appointment->save();
            $hashids = new Hashids(env('HASHIDS_SALT', 'your-default-salt'), 8);
            $appointment->hash = $hashids->encode($appointment->id);
            $appointment->save();
            if (!empty($appointmentDetails['service_pivot_data'])) {
                $appointment->services()->attach($appointmentDetails['service_pivot_data']);
            }
            
            DB::commit();

            // Load relationships before dispatching the job
            $appointment->load(['customer', 'staff', 'services']);

            $message = 'نوبت قدیمی با موفقیت ثبت شد.';

            return response()->json(['message' => $message, 'data' => new AppointmentResource($appointment)], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('خطا در ثبت نوبت قدیمی - مدل یافت نشد: ' . $e->getMessage());
            return response()->json(['message' => 'اطلاعات مرجع (مانند مشتری یا پرسنل) یافت نشد.'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('خطا در ثبت نوبت قدیمی: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در ثبت نوبت قدیمی رخ داد.', 'error_details_for_debug' => $e->getMessage()], 500);
        }
    }

    /**
     * Send reminder SMS for a specific appointment
     */
    public function sendReminderSms(Request $request, $salon_id, Appointment $appointment)
    {
        try {
            // Check if appointment belongs to the salon
            if ($appointment->salon_id != $salon_id) {
                return response()->json(['message' => 'نوبت متعلق به این سالن نیست.'], 403);
            }

            // Check if reminder SMS is enabled for this appointment
            if (!$appointment->send_reminder_sms) {
                return response()->json(['message' => 'ارسال پیامک یادآوری برای این نوبت فعال نیست.'], 400);
            }

            // Check if customer exists
            if (!$appointment->customer) {
                return response()->json(['message' => 'مشتری برای این نوبت یافت نشد.'], 404);
            }

            // Check if SMS already sent
            if (in_array($appointment->reminder_sms_status, ['sent', 'delivered'])) {
                return response()->json(['message' => 'پیامک یادآوری قبلاً ارسال شده است.'], 400);
            }

            // Dispatch the reminder SMS job
            \App\Jobs\SendAppointmentReminderSms::dispatch($appointment, $appointment->salon);

            // Update status to processing
            $appointment->update(['reminder_sms_status' => 'processing']);

            return response()->json(['message' => 'پیامک یادآوری با موفقیت ارسال شد.']);

        } catch (\Exception $e) {
            Log::error('خطا در ارسال پیامک یادآوری: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در ارسال پیامک یادآوری رخ داد.'], 500);
        }
    }
}
