<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceRenewalSetting;
use App\Models\RenewalReminderSetting;
use App\Models\SalonSmsTemplate;
use App\Models\SmsTemplateCategory;
use App\Models\Salon;
use App\Models\Customer;
use App\Models\Appointment;
use App\Traits\ChecksPackageFeature;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ServiceRenewalController extends Controller
{
    use ChecksPackageFeature;

    /**
      */
    public function getRenewalSettingsSummary(Request $request, Salon $salon): JsonResponse
    {
        $this->authorize('manageResources', $salon);
        if (!$this->checkRenewalReminderAccess($salon->id)) {
            return $this->renewalReminderAccessDeniedResponse();
        }
    $globalSetting = RenewalReminderSetting::where('salon_id', $salon->id)->first();
    $isActive = $globalSetting ? $globalSetting->is_active : false;
    $activeTemplateId = $globalSetting ? $globalSetting->active_template_id : null;

        $services = Service::where('salon_id', $salon->id)
            ->where('is_active', true)
            ->with(['renewalSetting'])
            ->get();

         foreach ($services as $service) {
            $customerCount = DB::table('appointment_service')
                ->join('appointments', 'appointment_service.appointment_id', '=', 'appointments.id')
                ->where('appointment_service.service_id', $service->id)
                ->where('appointments.status', 'completed')
                ->distinct('appointments.customer_id')
                ->count('appointments.customer_id');
            $service->customers_count = $customerCount;
        }

        $serviceData = [];
        foreach ($services as $service) {
            $setting = $service->renewalSetting;
            $serviceData[] = [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'customers_count' => $service->customers_count,
                'renewal_period_days' => $setting ? $setting->renewal_period_days : 30,
                'reminder_days_before' => $setting ? $setting->reminder_days_before : 7,
                'reminder_time' => $setting ? $setting->reminder_time : '10:00',
                'template_id' => $setting ? $setting->template_id : null,
                'is_active' => $setting ? $setting->is_active : false
            ];
        }

        return response()->json([
            'global_reminder_active' => $isActive,
            'global_template_id' => $activeTemplateId,
            'services' => $serviceData
        ]);
    }
    /**
      */
    public function getMultipleServiceSettings(Request $request, Salon $salon): JsonResponse
    {
        $this->authorize('manageResources', $salon);
        if (!$this->checkRenewalReminderAccess($salon->id)) {
            return $this->renewalReminderAccessDeniedResponse();
        }
        $serviceIds = $request->input('service_ids');
        if (!is_array($serviceIds) || empty($serviceIds)) {
            return response()->json(['message' => 'service_ids الزامی است و باید آرایه باشد.'], 422);
        }
        $settings = ServiceRenewalSetting::where('salon_id', $salon->id)
            ->whereIn('service_id', $serviceIds)
            ->get();
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->service_id] = [
                'is_active' => $setting->is_active,
                'renewal_period_days' => $setting->renewal_period_days,
                'reminder_days_before' => $setting->reminder_days_before,
                'reminder_time' => $setting->reminder_time,
                'template_id' => $setting->template_id
            ];
        }
         foreach ($serviceIds as $sid) {
            if (!isset($result[$sid])) {
                $result[$sid] = [
                    'is_active' => false,
                    'renewal_period_days' => 30,
                    'reminder_days_before' => 7,
                    'reminder_time' => '10:00',
                    'template_id' => null
                ];
            }
        }
        return response()->json($result);
    }

    /**
      */
    public function updateMultipleServiceSettings(Request $request, Salon $salon): JsonResponse
    {
        $this->authorize('manageResources', $salon);
        if (!$this->checkRenewalReminderAccess($salon->id)) {
            return $this->renewalReminderAccessDeniedResponse();
        }
        $data = $request->input('services');
        $templateId = $request->input('template_id');
        if ($templateId !== null) {
            RenewalReminderSetting::updateOrCreate(
                ['salon_id' => $salon->id],
                [
                    'active_template_id' => $templateId,
                    'is_active' => true
                ]
            );
        }
        if (!is_array($data) || empty($data)) {
            return response()->json(['message' => 'services الزامی است و باید آرایه باشد.'], 422);
        }
        $result = [];
        foreach ($data as $serviceId => $setting) {
            if ($templateId !== null) {
                $setting['template_id'] = $templateId;
            }
            $validator = Validator::make($setting, [
                'is_active' => 'required|boolean',
                'renewal_period_days' => 'required|integer|min:1|max:365',
                'reminder_days_before' => 'required|integer|min:1|max:30',
                'reminder_time' => ['required', 'string', 'regex:/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/'],
                'template_id' => 'nullable|exists:salon_sms_templates,id',
            ]);
            if ($validator->fails()) {
                $result[$serviceId] = ['success' => false, 'errors' => $validator->errors()];
                continue;
            }
            $service = Service::find($serviceId);
            if (!$service || $service->salon_id != $salon->id) {
                $result[$serviceId] = ['success' => false, 'errors' => ['service' => ['سرویس معتبر نیست']]];
                continue;
            }
            $settingModel = ServiceRenewalSetting::updateOrCreate(
                [
                    'salon_id' => $salon->id,
                    'service_id' => $serviceId
                ],
                [
                    'is_active' => $setting['is_active'],
                    'renewal_period_days' => $setting['renewal_period_days'],
                    'reminder_days_before' => $setting['reminder_days_before'],
                    'reminder_time' => $setting['reminder_time'],
                    'template_id' => $setting['is_active'] ? $setting['template_id'] : null
                ]
            );
            $result[$serviceId] = ['success' => true, 'renewal_setting' => $settingModel];
        }
        return response()->json($result);
    }

    use ChecksPackageFeature;

    /**
     */
    public function getServicesWithRenewalSettings(Request $request, Salon $salon): JsonResponse
    {
        $this->authorize('manageResources', $salon);
        
        if (!$this->checkRenewalReminderAccess($salon->id)) {
            return $this->renewalReminderAccessDeniedResponse();
        }
        
        try {
            $query = Service::where('salon_id', $salon->id)
                ->where('is_active', true)
                ->with(['renewalSetting.template'])
                ->withCount([
                    'appointments as customers_count' => function ($query) {
                        $query->select(DB::raw('COUNT(DISTINCT customer_id)'))
                              ->where('status', 'completed');
                    }
                ]);

            if ($request->filled('search')) {
                $searchTerm = $request->input('search');
                $query->where('name', 'like', "%{$searchTerm}%");
            }

            if ($request->filled('reminder_status')) {
                $reminderStatus = $request->input('reminder_status');
                if ($reminderStatus === 'active') {
                    $query->whereHas('renewalSetting', function ($q) {
                        $q->where('is_active', true);
                    });
                } elseif ($reminderStatus === 'inactive') {
                    $query->whereDoesntHave('renewalSetting', function ($q) {
                        $q->where('is_active', true);
                    });
                }
            }

            $sortBy = $request->input('sort_by', 'name');
            $sortDirection = $request->input('sort_direction', 'asc');
            
            if ($sortBy === 'customers_count') {
                $query->orderBy('customers_count', $sortDirection);
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }

            $services = $query->paginate($request->input('per_page', 15));

            $globalSetting = RenewalReminderSetting::where('salon_id', $salon->id)->first();

            return response()->json([
                'message' => 'لیست سرویس‌ها با موفقیت دریافت شد.',
                'services' => $services,
                'global_reminder_active' => $globalSetting ? $globalSetting->is_active : false,
                'total_services' => $services->total(),
                'active_reminders' => ServiceRenewalSetting::where('salon_id', $salon->id)
                    ->where('is_active', true)->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error in ServiceRenewalController@getServicesWithRenewalSettings:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'خطا در دریافت لیست سرویس‌ها.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     */
    public function getServiceRenewalSetting(Request $request, Salon $salon, Service $service): JsonResponse
    {
        $this->authorize('manageResources', $salon);
        
        if (!$this->checkRenewalReminderAccess($salon->id)) {
            return $this->renewalReminderAccessDeniedResponse();
        }
        
        try {
            if ($service->salon_id !== $salon->id) {
                return response()->json(['message' => 'سرویس متعلق به این سالن نیست.'], 403);
            }

            $setting = ServiceRenewalSetting::where('salon_id', $salon->id)
                ->where('service_id', $service->id)
                ->with('template')
                ->first();

            if (!$setting) {
                $setting = ServiceRenewalSetting::create([
                    'salon_id' => $salon->id,
                    'service_id' => $service->id,
                    'is_active' => false,
                    'renewal_period_days' => 30,
                    'reminder_days_before' => 7,
                    'reminder_time' => '10:00'
                ]);
            }

            return response()->json([
                'message' => 'تنظیمات سرویس با موفقیت دریافت شد.',
                'service' => $service,
                'renewal_setting' => $setting
            ]);

        } catch (\Exception $e) {
            Log::error('Error in ServiceRenewalController@getServiceRenewalSetting:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'خطا در دریافت تنظیمات سرویس.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     */
    public function updateServiceRenewalSetting(Request $request, Salon $salon, Service $service): JsonResponse
    {
        $this->authorize('manageResources', $salon);
        
        if (!$this->checkRenewalReminderAccess($salon->id)) {
            return $this->renewalReminderAccessDeniedResponse();
        }
        
        try {
            if ($service->salon_id !== $salon->id) {
                return response()->json(['message' => 'سرویس متعلق به این سالن نیست.'], 403);
            }

            $validator = Validator::make($request->all(), [
                'is_active' => 'required|boolean',
                'renewal_period_days' => 'required|integer|min:1|max:365',
                'reminder_days_before' => 'required|integer|min:1|max:30',
                'reminder_time' => ['required', 'string', 'regex:/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/'],
                'template_id' => 'required_if:is_active,true|nullable|exists:salon_sms_templates,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'داده‌های ورودی نامعتبر است.',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->is_active && $request->template_id) {
                $template = SalonSmsTemplate::find($request->template_id);
                if (!$template || ($template->salon_id !== null && $template->salon_id !== $salon->id)) {
                    return response()->json([
                        'message' => 'قالب انتخاب شده معتبر نیست.'
                    ], 422);
                }
            }

            $setting = ServiceRenewalSetting::updateOrCreate(
                [
                    'salon_id' => $salon->id,
                    'service_id' => $service->id
                ],
                [
                    'is_active' => $request->is_active,
                    'renewal_period_days' => $request->renewal_period_days,
                    'reminder_days_before' => $request->reminder_days_before,
                    'reminder_time' => $request->reminder_time,
                    'template_id' => $request->is_active ? $request->template_id : null
                ]
            );

            return response()->json([
                'message' => 'تنظیمات سرویس با موفقیت به‌روزرسانی شد.',
                'renewal_setting' => $setting
            ]);

        } catch (\Exception $e) {
            Log::error('Error in ServiceRenewalController@updateServiceRenewalSetting:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'خطا در به‌روزرسانی تنظیمات سرویس.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * فعال/غیرفعال کردن سریع یادآوری برای یک سرویس
     */
    public function toggleServiceReminder(Request $request, Salon $salon, Service $service): JsonResponse
    {
        $this->authorize('manageResources', $salon);
        
        if (!$this->checkRenewalReminderAccess($salon->id)) {
            return $this->renewalReminderAccessDeniedResponse();
        }
        
        try {
            if ($service->salon_id !== $salon->id) {
                return response()->json(['message' => 'سرویس متعلق به این سالن نیست.'], 403);
            }

            $validator = Validator::make($request->all(), [
                'is_active' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'داده‌های ورودی نامعتبر است.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $setting = ServiceRenewalSetting::where('salon_id', $salon->id)
                ->where('service_id', $service->id)
                ->first();

            if (!$setting) {
                $setting = ServiceRenewalSetting::create([
                    'salon_id' => $salon->id,
                    'service_id' => $service->id,
                    'is_active' => $request->is_active,
                    'renewal_period_days' => 30,
                    'reminder_days_before' => 7,
                    'reminder_time' => '10:00'
                ]);
            } else {
                $setting->update(['is_active' => $request->is_active]);
            }

            return response()->json([
                'message' => 'وضعیت یادآوری سرویس با موفقیت تغییر یافت.',
                'is_active' => $setting->is_active,
                'service_name' => $service->name
            ]);

        } catch (\Exception $e) {
            Log::error('Error in ServiceRenewalController@toggleServiceReminder:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'خطا در تغییر وضعیت یادآوری.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     */
    public function deleteServiceRenewalSetting(Request $request, Salon $salon, Service $service): JsonResponse
    {
        $this->authorize('manageResources', $salon);
        
        if (!$this->checkRenewalReminderAccess($salon->id)) {
            return $this->renewalReminderAccessDeniedResponse();
        }
        
        try {
            if ($service->salon_id !== $salon->id) {
                return response()->json(['message' => 'سرویس متعلق به این سالن نیست.'], 403);
            }

            $setting = ServiceRenewalSetting::where('salon_id', $salon->id)
                ->where('service_id', $service->id)
                ->first();

            if ($setting) {
                $setting->delete();
                return response()->json([
                    'message' => 'تنظیمات یادآوری سرویس با موفقیت حذف شد.'
                ]);
            }

            return response()->json([
                'message' => 'تنظیمات یادآوری برای این سرویس یافت نشد.'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error in ServiceRenewalController@deleteServiceRenewalSetting:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'خطا در حذف تنظیمات سرویس.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     */
    public function toggleGlobalReminder(Request $request, Salon $salon): JsonResponse
    {
        $this->authorize('manageResources', $salon);
        
        if (!$this->checkRenewalReminderAccess($salon->id)) {
            return $this->renewalReminderAccessDeniedResponse();
        }
        
        try {
            $validator = Validator::make($request->all(), [
                'is_active' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'داده‌های ورودی نامعتبر است.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $globalSetting = RenewalReminderSetting::updateOrCreate(
                ['salon_id' => $salon->id],
                ['is_active' => $request->is_active]
            );

            return response()->json([
                'message' => 'وضعیت کلی یادآوری ترمیم با موفقیت تغییر یافت.',
                'is_active' => $globalSetting->is_active
            ]);

        } catch (\Exception $e) {
            Log::error('Error in ServiceRenewalController@toggleGlobalReminder:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'خطا در تغییر وضعیت کلی یادآوری.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     */
    public function getRenewalStats(Request $request, Salon $salon): JsonResponse
    {
        $this->authorize('manageResources', $salon);
        
        if (!$this->checkRenewalReminderAccess($salon->id)) {
            return $this->renewalReminderAccessDeniedResponse();
        }
        
        try {
            $totalServices = Service::where('salon_id', $salon->id)
                ->where('is_active', true)
                ->count();

            $activeReminders = ServiceRenewalSetting::where('salon_id', $salon->id)
                ->where('is_active', true)
                ->count();

            $pendingReminders = DB::table('appointments')
                ->join('service_renewal_settings', function($join) {
                    $join->on('appointments.salon_id', '=', 'service_renewal_settings.salon_id');
                })
                ->join('appointment_service', 'appointments.id', '=', 'appointment_service.appointment_id')
                ->where('appointments.salon_id', $salon->id)
                ->where('appointments.status', 'completed')
                ->where('service_renewal_settings.is_active', true)
                ->whereRaw('DATE_ADD(appointments.appointment_date, INTERVAL service_renewal_settings.renewal_period_days DAY) <= CURDATE() + INTERVAL service_renewal_settings.reminder_days_before DAY')
                ->whereNotExists(function($query) {
                    $query->select(DB::raw(1))
                          ->from('renewal_reminder_logs')
                          ->whereRaw('renewal_reminder_logs.appointment_id = appointments.id')
                          ->where('renewal_reminder_logs.status', 'sent');
                })
                ->count();

            $sentToday = DB::table('renewal_reminder_logs')
                ->where('salon_id', $salon->id)
                ->where('status', 'sent')
                ->whereDate('sent_at', today())
                ->count();

            return response()->json([
                'message' => 'آمار یادآوری ترمیم با موفقیت دریافت شد.',
                'stats' => [
                    'total_services' => $totalServices,
                    'active_reminders' => $activeReminders,
                    'pending_reminders' => $pendingReminders,
                    'sent_today' => $sentToday,
                    'coverage_percentage' => $totalServices > 0 ? round(($activeReminders / $totalServices) * 100, 2) : 0
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in ServiceRenewalController@getRenewalStats:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'خطا در دریافت آمار.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}