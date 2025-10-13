<?php

namespace App\Http\Controllers;

use App\Http\Resources\RenewalReminderTemplateResource;
use App\Models\RenewalReminderSetting;
use App\Models\SalonSmsTemplate;
use App\Models\SmsTemplateCategory;
use App\Models\Salon;
use App\Traits\ChecksPackageFeature;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class RenewalReminderController extends Controller
{
    use ChecksPackageFeature;

    /**
     * دریافت لیست قالب‌های یادآوری ترمیم
     */
    public function getTemplates(Request $request): JsonResponse
    {
        try {
            // دریافت دسته‌بندی یادآوری ترمیم
            $category = SmsTemplateCategory::where('salon_id', null)
                ->where('name', 'یادآوری ترمیم')
                ->first();

            if (!$category) {
                return response()->json([
                    'message' => 'دسته‌بندی یادآوری ترمیم یافت نشد.',
                    'templates' => []
                ], 404);
            }

            // دریافت قالب‌های فعال
            $templates = SalonSmsTemplate::where('category_id', $category->id)
                ->where('salon_id', null) // قالب‌های سراسری
                ->where('is_active', true)
                ->get();

            return response()->json([
                'message' => 'قالب‌ها با موفقیت دریافت شدند.',
                'templates' => RenewalReminderTemplateResource::collection($templates)
            ]);

        } catch (\Exception $e) {
            Log::error('Error in RenewalReminderController@getTemplates:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'خطا در دریافت قالب‌ها.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * دریافت تنظیمات یادآوری ترمیم سالن
     */
    public function getSettings(Request $request, Salon $salon): JsonResponse
    {
        $this->authorize('manageResources', $salon);
        
        // بررسی دسترسی به فیچر یادآوری ترمیم
        if (!$this->checkRenewalReminderAccess($salon->id)) {
            return $this->renewalReminderAccessDeniedResponse();
        }
        
        try {
            $setting = RenewalReminderSetting::where('salon_id', $salon->id)->first();

            if (!$setting) {
                // ایجاد تنظیمات پیش‌فرض
                $setting = RenewalReminderSetting::create([
                    'salon_id' => $salon->id,
                    'is_active' => false,
                    'reminder_days_before' => 7,
                    'reminder_time' => '10:00'
                ]);
            }

            $response = [
                'is_active' => $setting->is_active,
                'active_template_id' => $setting->active_template_id,
                'reminder_days_before' => $setting->reminder_days_before,
                'reminder_time' => $setting->reminder_time,
                'active_template' => null
            ];

            // اضافه کردن اطلاعات قالب فعال
            if ($setting->active_template_id) {
                $activeTemplate = SalonSmsTemplate::find($setting->active_template_id);
                if ($activeTemplate) {
                    $response['active_template'] = [
                        'id' => $activeTemplate->id,
                        'title' => $activeTemplate->title,
                        'template' => $activeTemplate->template
                    ];
                }
            }

            return response()->json([
                'message' => 'تنظیمات با موفقیت دریافت شدند.',
                'settings' => $response
            ]);

        } catch (\Exception $e) {
            Log::error('Error in RenewalReminderController@getSettings:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'خطا در دریافت تنظیمات.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * به‌روزرسانی تنظیمات یادآوری ترمیم سالن
     */
    public function updateSettings(Request $request, Salon $salon): JsonResponse
    {
        $this->authorize('manageResources', $salon);
        
        // بررسی دسترسی به فیچر یادآوری ترمیم
        if (!$this->checkRenewalReminderAccess($salon->id)) {
            return $this->renewalReminderAccessDeniedResponse();
        }
        
        try {
            $validator = Validator::make($request->all(), [
                'is_active' => 'required|boolean',
                'active_template_id' => 'required_if:is_active,true|nullable|exists:salon_sms_templates,id',
                'reminder_days_before' => 'required|integer|min:1|max:30',
                'reminder_time' => 'required|string|regex:/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'داده‌های ورودی نامعتبر است.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // بررسی معتبر بودن قالب انتخاب شده
            if ($request->is_active && $request->active_template_id) {
                $template = SalonSmsTemplate::find($request->active_template_id);
                if (!$template || $template->salon_id !== null) {
                    return response()->json([
                        'message' => 'قالب انتخاب شده معتبر نیست.'
                    ], 422);
                }
            }

            $setting = RenewalReminderSetting::updateOrCreate(
                ['salon_id' => $salon->id],
                [
                    'is_active' => $request->is_active,
                    'active_template_id' => $request->is_active ? $request->active_template_id : null,
                    'reminder_days_before' => $request->reminder_days_before,
                    'reminder_time' => $request->reminder_time
                ]
            );

            return response()->json([
                'message' => 'تنظیمات با موفقیت به‌روزرسانی شدند.',
                'settings' => [
                    'is_active' => $setting->is_active,
                    'active_template_id' => $setting->active_template_id,
                    'reminder_days_before' => $setting->reminder_days_before,
                    'reminder_time' => $setting->reminder_time
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in RenewalReminderController@updateSettings:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'خطا در به‌روزرسانی تنظیمات.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * پیش‌نمایش قالب با جایگزینی متغیرها
     */
    public function previewTemplate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'template_id' => 'required|exists:salon_sms_templates,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'شناسه قالب نامعتبر است.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $template = SalonSmsTemplate::find($request->template_id);
            
            // داده‌های نمونه برای پیش‌نمایش
            $sampleData = [
                'customer_name' => 'خانم مریم رضایی',
                'salon_name' => 'سالن زیبایی نمونه',
                'service_name' => 'کاشت ناخن',
                'appointment_date' => '۱۴۰۳/۰۷/۱۵',
                'appointment_time' => '۱۰:۳۰'
            ];

            // جایگزینی متغیرها
            $previewMessage = $this->replaceTemplateVariables($template->template, $sampleData);

            return response()->json([
                'message' => 'پیش‌نمایش قالب با موفقیت ایجاد شد.',
                'preview' => [
                    'original_template' => $template->template,
                    'preview_message' => $previewMessage,
                    'sample_data' => $sampleData,
                    'estimated_parts' => ceil(mb_strlen($previewMessage) / 70),
                    'estimated_cost' => ceil(mb_strlen($previewMessage) / 70) * 100 // فرضی
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in RenewalReminderController@previewTemplate:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'خطا در ایجاد پیش‌نمایش.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * جایگزینی متغیرها در قالب
     */
    private function replaceTemplateVariables(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        return $template;
    }
}