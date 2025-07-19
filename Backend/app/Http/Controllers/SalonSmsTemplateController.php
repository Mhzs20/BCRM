<?php

namespace App\Http\Controllers;

use App\Models\SalonSmsTemplate;
use App\Models\Salon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalonSmsTemplateController extends Controller
{
    private const ALLOWED_EVENT_TYPES = [
        'appointment_confirmation',
        'appointment_reminder',
        'appointment_cancellation',
        'appointment_modification',
        'birthday_greeting',
        'service_specific_notes',
    ];

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        /** @var Salon $salon */
        $salon = $user->activeSalon;

        if (!$salon) {
            return response()->json(['message' => 'سالن فعالی برای شما انتخاب نشده است.'], 400);
        }
        $this->authorize('manageResources', $salon);

        $existingTemplatesCollection = $salon->smsTemplates()->get()->keyBy('event_type');
        $templates = [];

        foreach (self::ALLOWED_EVENT_TYPES as $eventType) {
            $templateModel = $existingTemplatesCollection->get($eventType);
            $templates[$eventType] = [
                'template' => $templateModel ? $templateModel->template : $this->getDefaultTemplateTextForEventType($eventType),
                'is_active' => $templateModel ? $templateModel->is_active : true,
                'description' => $this->getEventTypeDescription($eventType)
            ];
        }
        return response()->json(['data' => $templates]);
    }

    public function storeOrUpdate(Request $request): JsonResponse
    {
        $user = Auth::user();
        /** @var Salon $salon */
        $salon = $user->activeSalon;

        if (!$salon) {
            return response()->json(['message' => 'سالن فعالی برای شما انتخاب نشده است.'], 400);
        }
        $this->authorize('manageResources', $salon);

        $validated = $request->validate([
            'templates' => 'required|array',
            'templates.*.template' => 'required|string|max:1000',
            'templates.*.is_active' => 'required|boolean',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['templates'] as $eventType => $templateData) {
                if (!in_array($eventType, self::ALLOWED_EVENT_TYPES)) {
                    continue;
                }

                SalonSmsTemplate::updateOrCreate(
                    [
                        'salon_id' => $salon->id,
                        'event_type' => $eventType,
                    ],
                    [
                        'template' => $templateData['template'],
                        'is_active' => $templateData['is_active'],
                    ]
                );
            }
            DB::commit();
            return response()->json(['message' => 'قالب‌های پیامک با موفقیت ذخیره شدند.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error storing/updating SMS templates for salon ID {$salon->id}: " . $e->getMessage());
            return response()->json(['message' => 'خطا در ذخیره‌سازی قالب‌های پیامک.'], 500);
        }
    }

    private function getDefaultTemplateTextForEventType(string $eventType): string
    {
        switch ($eventType) {
            case 'appointment_confirmation':
                return "مشتری گرامی {{customer_name}}، نوبت شما در سالن {{salon_name}} برای تاریخ {{appointment_date}} ساعت {{appointment_time}} با موفقیت ثبت شد.";
            case 'appointment_reminder':
                return "یادآوری نوبت:\nمشتری گرامی {{customer_name}}، فردا ({{appointment_date}}) ساعت {{appointment_time}} در سالن {{salon_name}} منتظر شما هستیم.";
            case 'appointment_cancellation':
                return "مشتری گرامی {{customer_name}}، نوبت شما در سالن {{salon_name}} برای تاریخ {{appointment_date}} ساعت {{appointment_time}} لغو گردید.";
            case 'appointment_modification':
                return "مشتری گرامی {{customer_name}}، نوبت شما در سالن {{salon_name}} به تاریخ {{appointment_date}} ساعت {{appointment_time}} تغییر یافت.";
            case 'birthday_greeting':
                return "زادروزتان خجسته باد، {{customer_name}} عزیز! با آرزوی بهترین‌ها از طرف سالن {{salon_name}}.";
            case 'service_specific_notes':
                return "مشتری گرامی {{customer_name}}، برای نوبت {{service_name}} شما در تاریخ {{appointment_date}} ساعت {{appointment_time}}:\n{{service_specific_notes}}\nسالن {{salon_name}}";
            default:
                return "";
        }
    }

    private function getEventTypeDescription(string $eventType): string
    {
        $descriptions = [
            'appointment_confirmation' => 'تایید ثبت نوبت',
            'appointment_reminder'     => 'یادآوری نوبت',
            'appointment_cancellation' => 'لغو نوبت',
            'appointment_modification' => 'اصلاح نوبت',
            'birthday_greeting'      => 'تبریک تولد',
            'service_specific_notes' => 'نکات قبل از خدمت',
        ];
        return $descriptions[$eventType] ?? $eventType;
    }
}
