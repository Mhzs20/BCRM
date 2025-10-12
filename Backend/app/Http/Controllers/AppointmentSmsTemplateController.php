<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Models\SalonSmsTemplate;
use App\Models\SmsTemplateCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AppointmentSmsTemplateController extends Controller
{
    /**
     * دریافت تمپلیت‌های مناسب برای پیامک‌های نوبت‌گیری
     */
    public function getAppointmentTemplates(Request $request, Salon $salon): JsonResponse
    {
        // بررسی دسترسی
        if (Auth::user()->id !== $salon->user_id) {
            return response()->json(['message' => 'دسترسی غیرمجاز'], 403);
        }

        try {
            // دریافت تمپلیت‌های سیستم برای نوبت‌گیری
            $systemTemplates = SalonSmsTemplate::whereNull('salon_id')
                ->where('template_type', 'system_event')
                ->whereIn('event_type', ['appointment_confirmation', 'appointment_reminder'])
                ->where('is_active', true)
                ->get()
                ->map(function ($template) {
                    return [
                        'id' => $template->id,
                        'title' => $this->getEventTypeTitle($template->event_type),
                        'template' => $template->template,
                        'event_type' => $template->event_type,
                        'template_type' => 'system_event',
                        'category' => 'سیستم',
                        'is_active' => $template->is_active
                    ];
                });

            // دریافت دسته‌بندی‌های مرتبط
            $appointmentCategories = SmsTemplateCategory::whereNull('salon_id')
                ->whereIn('name', ['ثبت نوبت', 'یادآوری نوبت', 'نوبت‌گیری'])
                ->with(['templates' => function($query) {
                    $query->where('template_type', 'custom')
                        ->where('is_active', true)
                        ->whereNull('salon_id');
                }])
                ->get();

            $customTemplates = collect();
            foreach ($appointmentCategories as $category) {
                foreach ($category->templates as $template) {
                    $customTemplates->push([
                        'id' => $template->id,
                        'title' => $template->title,
                        'template' => $template->template,
                        'event_type' => null,
                        'template_type' => 'custom',
                        'category' => $category->name,
                        'is_active' => $template->is_active
                    ]);
                }
            }

            // دریافت سایر تمپلیت‌های سفارشی (بدون دسته‌بندی خاص)
            $otherCustomTemplates = SalonSmsTemplate::whereNull('salon_id')
                ->where('template_type', 'custom')
                ->where('is_active', true)
                ->whereNull('category_id')
                ->get()
                ->map(function ($template) {
                    return [
                        'id' => $template->id,
                        'title' => $template->title,
                        'template' => $template->template,
                        'event_type' => null,
                        'template_type' => 'custom',
                        'category' => 'عمومی',
                        'is_active' => $template->is_active
                    ];
                });

            $allTemplates = $systemTemplates->concat($customTemplates)->concat($otherCustomTemplates);

            // تفکیک بر اساس نوع
            $confirmationTemplates = $allTemplates->filter(function ($template) {
                return $template['event_type'] === 'appointment_confirmation' || 
                       in_array($template['category'], ['ثبت نوبت', 'نوبت‌گیری', 'عمومی']);
            })->values();

            $reminderTemplates = $allTemplates->filter(function ($template) {
                return $template['event_type'] === 'appointment_reminder' || 
                       in_array($template['category'], ['یادآوری نوبت', 'نوبت‌گیری', 'عمومی']);
            })->values();

            return response()->json([
                'confirmation_templates' => $confirmationTemplates,
                'reminder_templates' => $reminderTemplates,
                'stats' => [
                    'total_confirmation' => $confirmationTemplates->count(),
                    'active_confirmation' => $confirmationTemplates->where('is_active', true)->count(),
                    'total_reminder' => $reminderTemplates->count(),
                    'active_reminder' => $reminderTemplates->where('is_active', true)->count(),
                ],
                'message' => 'تمپلیت‌ها با موفقیت دریافت شدند'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطا در دریافت تمپلیت‌ها',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تنظیم تمپلیت پیش‌فرض برای سالن
     */
    public function setDefaultTemplate(Request $request, Salon $salon): JsonResponse
    {
        // بررسی دسترسی
        if (Auth::user()->id !== $salon->user_id) {
            return response()->json(['message' => 'دسترسی غیرمجاز'], 403);
        }

        $data = $request->validate([
            'template_type' => 'required|in:confirmation,reminder',
            'template_id' => 'required|integer|exists:salon_sms_templates,id'
        ]);

        try {
            $template = SalonSmsTemplate::findOrFail($data['template_id']);
            
            if (!$template->is_active) {
                return response()->json(['message' => 'نمی‌توان تمپلیت غیرفعال را به عنوان پیش‌فرض انتخاب کرد'], 422);
            }

            // ذخیره تنظیمات پیش‌فرض در جدول settings
            $settingKey = $data['template_type'] === 'confirmation' ? 
                'default_confirmation_template_id' : 'default_reminder_template_id';

            \App\Models\Setting::updateOrCreate([
                'salon_id' => $salon->id,
                'key' => $settingKey
            ], [
                'value' => $data['template_id']
            ]);

            return response()->json([
                'message' => 'تمپلیت پیش‌فرض با موفقیت تنظیم شد',
                'template' => [
                    'id' => $template->id,
                    'title' => $template->title,
                    'type' => $data['template_type']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطا در تنظیم تمپلیت پیش‌فرض',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * دریافت عنوان فارسی برای نوع رویداد
     */
    private function getEventTypeTitle(string $eventType): string
    {
        return match($eventType) {
            'appointment_confirmation' => 'تایید نوبت (سیستم)',
            'appointment_reminder' => 'یادآوری نوبت (سیستم)',
            default => 'تمپلیت سیستم'
        };
    }
}