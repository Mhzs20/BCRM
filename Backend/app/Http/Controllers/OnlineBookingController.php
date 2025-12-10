<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Hashids\Hashids;
use Morilog\Jalali\Jalalian;
use App\Jobs\SendAppointmentConfirmationSms;

class OnlineBookingController extends Controller
{
    /**
     */
    public function showBookingPage($salonId)
    {
        try {
            $salon = Salon::with(['services' => function($query) {
                $query->where('is_active', true);
            }])->findOrFail($salonId);

            // اگر سالن فعال نباشد، پیغام مناسب نمایش داده شود
            if (!$salon->is_active) {
                return view('booking.salon-inactive', compact('salon'));
            }

            return view('booking.index', compact('salon'));
        } catch (\Exception $e) {
            return view('booking.salon-not-found');
        }
    }

    /**
     * Render success page for booking by appointment id or fallback to query params
     */
    public function successPage(Request $request)
    {
        $appointment = null;
        $appointmentId = $request->query('tracking_code') ?? $request->query('appointment_id');
        if ($appointmentId) {
            $appointment = Appointment::with(['customer', 'staff', 'services', 'salon'])->find($appointmentId);
        }

        if ($appointment) {
            $service = $appointment->services->pluck('name')->implode(', ');
            $date = verta($appointment->appointment_date)->format('Y/m/d') . ' - ' . $appointment->start_time;
            $operator = $appointment->staff->full_name ?? null;
            $tracking_code = $appointment->id;
            $hash = $appointment->hash ?? null;
            $salon = $appointment->salon;
            $salon_id = $salon->id ?? null;
            $salon_name = $salon->name ?? null;
            $salon_phone = $salon->mobile ?? null;
            $salon_image = $salon->image ?? null;
            $status = $appointment->status;

            if ($status === 'pending_confirmation') {
                return view('booking.pending', compact('service', 'date', 'operator', 'tracking_code', 'salon_id', 'salon_name', 'salon_phone', 'salon_image', 'hash'));
            }

            return view('booking.success', compact('service', 'date', 'operator', 'tracking_code', 'salon_id', 'salon_name', 'salon_phone', 'salon_image', 'hash'));
        }

        // If we don't have appointment, fallback to query params
        return view('booking.success');
    }

    /**
     * Render error page when booking fails for any reason. Accepts a message query param.
     */
    public function errorPage(Request $request)
    {
        $message = $request->query('message', 'اقدام شما برای رزرو نوبت موفقیت آمیز نبود.');
        $salonId = $request->query('salon_id');
        $salon = null;
        $salon_name = null;
        $salon_phone = null;
        $salon_image = null;
        if ($salonId) {
            $salon = Salon::find($salonId);
            if ($salon) {
                $salon_name = $salon->name;
                $salon_phone = $salon->mobile ?? null;
                $salon_image = $salon->image ?? null;
            }
        }
        return view('booking.error', compact('message', 'salon_name', 'salon_phone', 'salon_image', 'salonId'));
    }

    /**
    */
    public function getServices($salonId)
    {
        try {
            // Eager load salon with active staff to avoid N+1 queries in loop
            $salon = Salon::with(['staff' => function($query) {
                $query->where('is_active', true);
            }])->findOrFail($salonId);
            
            if (!$salon->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'سالن در حال حاضر فعال نیست'
                ], 404);
            }

            $services = Service::where('salon_id', $salonId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function ($service) use ($salon) {
                     // Pass the loaded salon object to avoid re-querying
                     $nextAvailable = $this->getNextAvailableSlot($salon, $service->id);
                    
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'price' => $service->price,
                        'next_available' => $nextAvailable ? [
                            'date' => $nextAvailable['date'],
                            'time' => $nextAvailable['time'],
                            'jalali_date' => $nextAvailable['jalali_date']
                        ] : null
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $services
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in getServices: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت خدمات'
            ], 500);
        }
    }

    /**
     * دریافت تایم‌های خالی برای رزرو
     */
    public function getAvailableTimes(Request $request, $salonId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'date' => 'required|date|after_or_equal:today',
                'service_ids' => 'required|array',
                'service_ids.*' => 'exists:services,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'داده‌های ورودی نامعتبر',
                    'errors' => $validator->errors()
                ], 422);
            }

            $salon = Salon::findOrFail($salonId);
            $date = Carbon::parse($request->date);
            $serviceIds = $request->service_ids;

            // بررسی اینکه آیا خدمات متعلق به این سالن هستند
            $validServices = Service::whereIn('id', $serviceIds)
                ->where('salon_id', $salonId)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();

            if (count($validServices) !== count($serviceIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'برخی از خدمات انتخابی نامعتبر هستند'
                ], 422);
            }

            $availableTimes = $this->getAvailableTimesForDate($salonId, $date, $serviceIds);

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $date->format('Y-m-d'),
                    'jalali_date' => verta($date)->format('Y/m/d'),
                    'day_name' => verta($date)->format('l'),
                    'available_times' => $availableTimes
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تایم‌های خالی'
            ], 500);
        }
    }

    /**
     * ثبت رزرو جدید
     */
    public function reserveAppointment(Request $request, $salonId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_name' => 'required|string|max:255',
                'customer_mobile' => 'required|string|regex:/^09[0-9]{9}$/',
                'appointment_date' => 'required|date|after_or_equal:today',
                'start_time' => 'required|date_format:H:i',
                'service_ids' => 'required|array',
                'service_ids.*' => 'exists:services,id',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'داده‌های ورودی نامعتبر',
                    'errors' => $validator->errors()
                ], 422);
            }

            $salon = Salon::findOrFail($salonId);
            
            if (!$salon->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'سالن در حال حاضر فعال نیست'
                ], 404);
            }

            DB::beginTransaction();

            // بررسی یا ایجاد مشتری
            $customer = Customer::where('salon_id', $salonId)
                ->where('phone_number', $request->customer_mobile)
                ->first();

            if (!$customer) {
                $customerData = [
                    'salon_id' => $salonId,
                    'name' => $request->customer_name,
                    'phone_number' => $request->customer_mobile
                ];

                // If referral_source is provided, try to find matching how_introduced
                if ($request->has('referral_source') && !empty($request->referral_source)) {
                    $howIntroduced = \App\Models\HowIntroduced::where('salon_id', $salonId)
                        ->where('name', $request->referral_source)
                        ->first();
                    
                    if ($howIntroduced) {
                        $customerData['how_introduced_id'] = $howIntroduced->id;
                    }
                }

                $customer = Customer::create($customerData);
            } else {
                // به‌روزرسانی نام مشتری در صورت تفاوت
                if ($customer->name !== $request->customer_name) {
                    $customer->update(['name' => $request->customer_name]);
                }
            }

            // بررسی در دسترس بودن تایم انتخابی
            $appointmentDateTime = Carbon::parse($request->appointment_date . ' ' . $request->start_time);
            
            if (!$this->isTimeSlotAvailable($salonId, $appointmentDateTime, $request->service_ids)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'تایم انتخابی در دسترس نیست'
                ], 422);
            }

            // محاسبه مدت زمان کل خدمات (فرض: هر خدمت ۶۰ دقیقه)
            $totalDuration = count($request->service_ids) * 60;
            $endTime = $appointmentDateTime->copy()->addMinutes($totalDuration);

            // یافتن اولین کارمند فعال برای تخصیص نوبت
            $staff = \App\Models\Staff::where('salon_id', $salonId)
                ->where('is_active', true)
                ->first();

            if (!$staff) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'هیچ کارمند فعالی برای این سالن یافت نشد'
                ], 422);
            }

            // Determine default status from salon settings
            $defaultStatus = 'pending_confirmation';
            if (isset($salon->online_booking_settings) && isset($salon->online_booking_settings['default_booking_status'])) {
                $defaultStatus = $salon->online_booking_settings['default_booking_status'];
            }

            // ایجاد نوبت
            $appointment = Appointment::create([
                'salon_id' => $salonId,
                'customer_id' => $customer->id,
                'staff_id' => $staff->id,
                'appointment_date' => $request->appointment_date,
                'start_time' => $request->start_time,
                'end_time' => $endTime->format('H:i'),
                'status' => $defaultStatus,
                'notes' => $request->notes,
                'source' => 'online_booking'
            ]);

            // اتصال خدمات به نوبت
            $appointment->services()->attach($request->service_ids);

            DB::commit();

            // If status is confirmed, send confirmation SMS immediately
            if ($appointment->status === 'confirmed') {
                SendAppointmentConfirmationSms::dispatch($customer, $appointment, $salon, null);
            }

            // Generate hash for appointment (if not present) and save
            try {
                $hashids = new Hashids(env('HASHIDS_SALT', 'your-default-salt'), 8);
                $appointment->hash = $hashids->encode($appointment->id);
                $appointment->save();
            } catch (\Exception $e) {
                // ignore hash generation errors
            }

            // Return JSON success response expected by AJAX calls
            return response()->json([
                'success' => true,
                'message' => 'نوبت شما با موفقیت ثبت شد',
                'data' => [
                    'appointment_id' => $appointment->id,
                    'customer_name' => $customer->name,
                    'appointment_date' => verta($appointment->appointment_date)->format('Y/m/d'),
                    'start_time' => $appointment->start_time,
                    'services' => $appointment->services->pluck('name')->toArray(),
                    'salon_name' => $salon->name,
                    'salon_id' => $salon->id,
                    'salon_phone' => $salon->mobile ?? null,
                    'operator' => $staff->full_name ?? null,
                    'hash' => $appointment->hash ?? null
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Booking reservation error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'خطا در ثبت نوبت'
            ], 500);
        }
    }

    /**
     * پیدا کردن اولین نوبت خالی برای یک سرویس
     */
    private function getNextAvailableSlot($salonOrId, $serviceId = null)
    {
        $today = Carbon::today();
        
        // Limit search to 14 days to improve performance
        for ($i = 0; $i < 14; $i++) {
            $checkDate = $today->copy()->addDays($i);
            $serviceIds = $serviceId ? [$serviceId] : [];
            
            // Pass the salon object/ID directly
            $availableTimes = $this->getAvailableTimesForDate($salonOrId, $checkDate, $serviceIds);
            
            if (!empty($availableTimes)) {
                // Find first available slot (is_available = true)
                foreach ($availableTimes as $slot) {
                    if (isset($slot['is_available']) && $slot['is_available']) {
                        return [
                            'date' => $checkDate->format('Y-m-d'),
                            'time' => $slot['time'],
                            'jalali_date' => Jalalian::fromCarbon($checkDate)->format('Y/m/d')
                        ];
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * دریافت تایم‌های خالی برای یک روز خاص
     */
    private function getAvailableTimesForDate($salonOrId, $date, $serviceIds = [])
    {
        // دریافت تنظیمات کاری سالن
        if ($salonOrId instanceof Salon) {
            $salon = $salonOrId;
            $salonId = $salon->id;
        } else {
            $salonId = $salonOrId;
            $salon = Salon::with(['staff' => function($query) {
                $query->where('is_active', true);
            }])->findOrFail($salonId);
        }
        
        // پیدا کردن اپراتور مناسب
        // در حال حاضر اولین اپراتور فعال را انتخاب می‌کنیم که این سرویس‌ها را انجام می‌دهد
        // در آینده می‌توان منطق پیچیده‌تری برای انتخاب اپراتور داشت (مثلاً کمترین نوبت)
        $operator = null;
        
        if (!empty($serviceIds)) {
            // پیدا کردن اپراتوری که همه سرویس‌های انتخاب شده را انجام می‌دهد
            foreach ($salon->staff as $staff) {
                $staffServices = $staff->services()->pluck('services.id')->toArray();
                if (count(array_intersect($serviceIds, $staffServices)) === count($serviceIds)) {
                    $operator = $staff;
                    break;
                }
            }
        }
        
        // اگر اپراتوری پیدا نشد یا سرویسی انتخاب نشده، از اولین اپراتور فعال استفاده کن
        if (!$operator && $salon->staff->count() > 0) {
            $operator = $salon->staff->first();
        }
        
        // اگر هیچ اپراتوری نیست، هیچ نوبتی وجود ندارد
        if (!$operator) {
            return [];
        }

        $operatorName = !empty(trim($operator->full_name)) ? $operator->full_name : 'اپراتور سالن';
        
        // دریافت برنامه کاری اپراتور برای روز مورد نظر
        // Carbon returns 0 for Sunday, 1 for Monday, ..., 6 for Saturday
        // We need to convert it to Iranian week: 0 for Saturday, 1 for Sunday, ..., 6 for Friday
        $carbonDayOfWeek = $date->dayOfWeek; 
        $dayOfWeek = ($carbonDayOfWeek + 1) % 7;
        
        $schedule = $operator->schedules()->where('day_of_week', $dayOfWeek)->where('is_active', true)->first();
        
        // اگر برنامه کاری برای این روز ندارد، یعنی تعطیل است
        if (!$schedule) {
            return [];
        }
        
        // دریافت زمان‌های استراحت اپراتور
        $breaks = $operator->breaks()->where('weekday', $dayOfWeek)->get();
        
        // تولید اسلات‌های زمانی بر اساس ساعت کاری (هر 30 دقیقه)
        $startTime = Carbon::parse($schedule->start_time);
        $endTime = Carbon::parse($schedule->end_time);
        $workingHours = [];
        
        while ($startTime->copy()->addMinutes(30)->lte($endTime)) {
            $timeStr = $startTime->format('H:i');
            
            // چک کردن تداخل با زمان استراحت
            $isBreak = false;
            foreach ($breaks as $break) {
                $breakStart = Carbon::parse($break->start_time);
                $breakEnd = Carbon::parse($break->end_time);
                
                // اگر اسلات زمانی با زمان استراحت تداخل دارد
                // شرط تداخل: زمان شروع اسلات < پایان استراحت AND زمان پایان اسلات > شروع استراحت
                $slotEnd = $startTime->copy()->addMinutes(30);
                
                // ساده‌تر: اگر زمان شروع اسلات داخل بازه استراحت باشد
                if ($startTime->gte($breakStart) && $startTime->lt($breakEnd)) {
                    $isBreak = true;
                    break;
                }
            }
            
            if (!$isBreak) {
                $workingHours[] = $timeStr;
            }
            
            $startTime->addMinutes(30);
        }

        // دریافت نوبت‌های رزرو شده برای آن روز و آن اپراتور
        // همه نوبت‌ها به جز کنسلی‌ها را می‌گیریم تا مطمئن شویم تایم پر است
        $bookedAppointments = Appointment::where('salon_id', $salonId)
            ->where('staff_id', $operator->id)
            ->where('appointment_date', $date->format('Y-m-d'))
            ->whereNotIn('status', ['canceled', 'cancelled', 'rejected'])
            ->get();

        $bookedTimes = $bookedAppointments->pluck('start_time')
            ->map(function ($time) {
                return Carbon::parse($time)->format('H:i');
            })
            ->toArray();

        $availableTimes = [];
        $now = Carbon::now();

        foreach ($workingHours as $time) {
            $slotDateTime = Carbon::parse($date->format('Y-m-d') . ' ' . $time);
            
            // بررسی اینکه آیا تایم گذشته است یا خیر
            if ($slotDateTime->isPast()) {
                continue;
            }

            // بررسی اینکه آیا تایم رزرو شده است یا خیر
            $isBooked = in_array($time, $bookedTimes);
            
            $availableTimes[] = [
                'time' => $time,
                'display_time' => $this->formatTimeForDisplay($time),
                'operator_name' => $operatorName,
                'operator_id' => $operator->id,
                'is_available' => !$isBooked,
                'is_booked' => $isBooked
            ];
        }

        return $availableTimes;
    }

    /**
     * بررسی در دسترس بودن یک تایم خاص
     */
    private function isTimeSlotAvailable($salonId, $dateTime, $serviceIds)
    {
        $conflictingAppointments = Appointment::where('salon_id', $salonId)
            ->where('appointment_date', $dateTime->format('Y-m-d'))
            ->where('start_time', $dateTime->format('H:i'))
            ->whereIn('status', ['confirmed', 'pending', 'pending_confirmation'])
            ->exists();

        return !$conflictingAppointments;
    }

    /**
     * فرمت نمایش زمان
     */
    private function formatTimeForDisplay($time)
    {
        $hour = (int) substr($time, 0, 2);
        $minute = substr($time, 3, 2);
        
        $period = $hour < 12 ? 'قبل از ظهر' : 'بعد از ظهر';
        
        // تبدیل اعداد انگلیسی به فارسی
        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        $persianTime = str_replace($englishDigits, $persianDigits, $time);
        
        return $persianTime . ' ' . $period;
    }

    /**
     * تبدیل تاریخ جلالی به میلادی
     */
    private function persianToGregorian($pYear, $pMonth, $pDay)
    {
        $pYear = (int)$pYear; $pMonth = (int)$pMonth; $pDay = (int)$pDay;
        $epbase = $pYear - ($pYear >= 0 ? 474 : 473);
        $epyear = 474 + ($epbase % 2820);
        $mdays = $pMonth <= 7 ? ($pMonth - 1) * 31 : (($pMonth - 1) * 30) + 6;
        $jdn = $pDay + $mdays + floor(($epyear * 682 - 110) / 2816) + ($epyear - 1) * 365 + floor($epbase / 2820) * 1029983 + 1948321;

        $j = $jdn + 32044;
        $g = floor($j / 146097);
        $dg = $j % 146097;
        $c = floor((floor($dg / 36524) + 1) * 3 / 4);
        $dc = $dg - $c * 36524;
        $b = floor($dc / 1461);
        $db = $dc % 1461;
        $a = floor((floor($db / 365) + 1) * 3 / 4);
        $da = $db - $a * 365;
        $y = $g * 400 + $c * 100 + $b * 4 + $a;
        $m = floor(($da * 5 + 308) / 153) - 2;
        $d = $da - floor(($m + 4) * 153 / 5) + 122;
        $Y = $y - 4800 + floor(($m + 2) / 12);
        $M = ($m + 2) % 12 + 1;
        $D = $d + 1;
        return sprintf('%04d-%02d-%02d', $Y, $M, $D);
    }
    /**
     * دریافت داده‌های تقویم از API (Proxy برای جلوگیری از CORS)
     */
    public function getCalendarDataProxy(Request $request)
    {
        $year = $request->query('year', verta()->year);
        $data = $this->getCalendarData($year);
        
        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت داده‌های تقویم'
            ], 500);
        }
        
        return response()->json($data);
    }

    /**
     * دریافت داده‌های تقویم از API
     */
    private function getCalendarData($year)
    {
        try {
            $response = Http::timeout(10)->get("https://persian-calendar-api.sajjadth.workers.dev/?year={$year}");

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if (!$data || !is_array($data)) {
                return null;
            }

            // پردازش داده‌ها و اضافه کردن weekdayPersian
            foreach ($data as $monthIndex => &$month) {
                if (!isset($month['days']) || !is_array($month['days'])) continue;

                foreach ($month['days'] as &$day) {
                    if (($day['disabled'] ?? false) || !isset($day['day']['jalali'])) continue;

                    $jalaliStr = $day['day']['jalali'];
                    $jalali = intval(str_replace(['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'], ['0','1','2','3','4','5','6','7','8','9'], $jalaliStr));

                    // محاسبه gregorian و weekdayPersian
                    try {
                        $persianMonth = $monthIndex + 1; // 1-based
                        $greg = $this->persianToGregorian($year, $persianMonth, $jalali);

                        $d = new \DateTime($greg . 'T00:00:00');
                        $jsWeek = (int)$d->format('w'); // 0=Sun .. 6=Sat
                        $weekdayPersian = ($jsWeek + 1) % 7; // 0=Sat .. 6=Fri

                        $day['jalali'] = $jalali;
                        $day['gregorian'] = $greg;
                        $day['weekdayPersian'] = $weekdayPersian;
                    } catch (\Exception $e) {
                        $day['jalali'] = $jalali;
                        $day['gregorian'] = null;
                        $day['weekdayPersian'] = null;
                    }
                }
            }

            // برگرداندن کل داده‌های سال
            return $data;

        } catch (\Exception $e) {
            // در صورت خطا، null برگردان
            return null;
        }
    }

}