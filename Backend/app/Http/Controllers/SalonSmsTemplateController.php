<?php

namespace App\Http\Controllers;

use App\Models\SalonSmsTemplate;
use App\Models\Salon;
use App\Models\SmsTemplateCategory;
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
        'exclusive_link', // لینک اختصاصی رزرو آنلاین
    ];

    public function index(Request $request): JsonResponse
    {
        // Global system event templates (salon_id is NULL)
        $existingTemplatesCollection = SalonSmsTemplate::whereNull('salon_id')
            ->where('template_type', 'system_event')
            ->get()
            ->keyBy('event_type');

        $systemTemplates = [];
        foreach (self::ALLOWED_EVENT_TYPES as $eventType) {
            $templateModel = $existingTemplatesCollection->get($eventType);
            $systemTemplates[$eventType] = [
                'template' => $templateModel ? $templateModel->template : $this->getDefaultTemplateTextForEventType($eventType),
                'is_active' => $templateModel ? $templateModel->is_active : true,
                'description' => $this->getEventTypeDescription($eventType),
                'id' => $templateModel?->id,
                'template_type' => 'system_event'
            ];
        }

        // Global custom categories & templates (categories with salon_id NULL)
        $categories = SmsTemplateCategory::whereNull('salon_id')
            ->with(['templates' => function($q){
                $q->where('template_type', 'custom')->whereNull('salon_id')->orderByDesc('id');
            }])->get();

        $customCategories = $categories->map(function($cat){
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'templates' => $cat->templates->map(function($t){
                    return [
                        'id' => $t->id,
                        'title' => $t->title,
                        'template' => $t->template,
                        'is_active' => $t->is_active,
                        'template_type' => $t->template_type,
                        'category_id' => $t->category_id,
                        'created_at' => $t->created_at,
                        'updated_at' => $t->updated_at,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'data' => [
                'system_templates' => $systemTemplates,
                'custom_categories' => $customCategories,
            ]
        ]);
    }

    public function storeOrUpdate(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user->is_superadmin) {
            return response()->json(['message' => 'فقط ادمین سیستم می‌تواند قالب‌های سراسری را ویرایش کند.'], 403);
        }

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

                $template = SalonSmsTemplate::updateOrCreate(
                    [
                        'salon_id' => null,
                        'event_type' => $eventType,
                        'template_type' => 'system_event'
                    ],
                    [
                        'template' => $templateData['template'],
                        'is_active' => $templateData['is_active'],
                    ]
                );
                
                // محاسبه و به‌روزرسانی estimated_parts و estimated_cost
                $template->updateEstimatedValues();
            }
            DB::commit();
            return response()->json(['message' => 'قالب‌های پیامک با موفقیت ذخیره شدند.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error storing/updating GLOBAL SMS templates: " . $e->getMessage());
            return response()->json(['message' => 'خطا در ذخیره‌سازی قالب‌های پیامک.'], 500);
        }
    }

    // =============== Custom Categories ==================
    public function createCategory(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user->is_superadmin) {
            return response()->json(['message' => 'فقط ادمین سیستم می‌تواند دسته‌بندی سراسری ایجاد کند.'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:100'
        ]);

        $category = SmsTemplateCategory::create([
            'salon_id' => null,
            'name' => $data['name']
        ]);

        return response()->json(['message' => 'دسته‌بندی ایجاد شد.', 'data' => $category]);
    }

    public function updateCategory(Request $request, SmsTemplateCategory $category): JsonResponse
    {
        if (!Auth::user()->is_superadmin) {
            return response()->json(['message' => 'مجاز نیستید.'], 403);
        }
        $data = $request->validate([
            'name' => 'required|string|max:100'
        ]);
        $category->update($data);
        return response()->json(['message' => 'دسته‌بندی بروزرسانی شد.', 'data' => $category]);
    }

    public function deleteCategory(Request $request, SmsTemplateCategory $category): JsonResponse
    {
        if (!Auth::user()->is_superadmin) {
            return response()->json(['message' => 'مجاز نیستید.'], 403);
        }
        // Optionally move templates to null category
        $category->templates()->update(['category_id' => null]);
        $category->delete();
        return response()->json(['message' => 'دسته‌بندی حذف شد.']);
    }

    // =============== Custom Templates ==================
    public function createCustomTemplate(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user->is_superadmin) {
            return response()->json(['message' => 'فقط ادمین سیستم می‌تواند قالب سراسری ایجاد کند.'], 403);
        }

        $data = $request->validate([
            'category_id' => 'nullable|integer|exists:sms_template_categories,id',
            'title' => 'required|string|max:150',
            'template' => 'required|string|max:1000',
            'is_active' => 'sometimes|boolean'
        ]);

        if (isset($data['category_id'])) {
            $category = SmsTemplateCategory::find($data['category_id']);
            if (!$category || !is_null($category->salon_id)) {
                return response()->json(['message' => 'دسته‌بندی معتبر نیست.'], 422);
            }
        }

        $custom = SalonSmsTemplate::create([
            'salon_id' => null,
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'template' => $data['template'],
            'is_active' => $data['is_active'] ?? true,
            'template_type' => 'custom'
        ]);
        
        // محاسبه و به‌روزرسانی estimated_parts و estimated_cost
        $custom->updateEstimatedValues();

        return response()->json(['message' => 'قالب ایجاد شد.', 'data' => $custom]);
    }

    public function updateCustomTemplate(Request $request, SalonSmsTemplate $template): JsonResponse
    {
        if (!Auth::user()->is_superadmin) {
            return response()->json(['message' => 'مجاز نیستید.'], 403);
        }
        if ($template->template_type !== 'custom') {
            return response()->json(['message' => 'امکان ویرایش این نوع قالب وجود ندارد.'], 422);
        }
        $data = $request->validate([
            'category_id' => 'nullable|integer|exists:sms_template_categories,id',
            'title' => 'required|string|max:150',
            'template' => 'required|string|max:1000',
            'is_active' => 'sometimes|boolean'
        ]);
        if (isset($data['category_id'])) {
            $category = SmsTemplateCategory::find($data['category_id']);
            if (!$category || !is_null($category->salon_id)) {
                return response()->json(['message' => 'دسته‌بندی معتبر نیست.'], 422);
            }
        }
        $template->update($data);
        
        // محاسبه و به‌روزرسانی estimated_parts و estimated_cost
        $template->updateEstimatedValues();
        
        return response()->json(['message' => 'قالب بروزرسانی شد.', 'data' => $template]);
    }

    public function deleteCustomTemplate(Request $request, SalonSmsTemplate $template): JsonResponse
    {
        if (!Auth::user()->is_superadmin) {
            return response()->json(['message' => 'مجاز نیستید.'], 403);
        }
        if ($template->template_type !== 'custom') {
            return response()->json(['message' => 'امکان حذف این نوع قالب وجود ندارد.'], 422);
        }
        $template->delete();
        return response()->json(['message' => 'قالب حذف شد.']);
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
            case 'exclusive_link':
                return "سلام {{customer_name}}، برای رزرو آنلاین در سالن {{salon_name}} روی لینک زیر کلیک کنید: {{details_url}}";
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
            'exclusive_link' => 'لینک اختصاصی رزرو آنلاین',
        ];
        return $descriptions[$eventType] ?? $eventType;
    }

    /**
     * Return available templates for exclusive link sending (system + global custom)
     */
    public function exclusiveTemplates(): JsonResponse
    {
        $systemTemplate = SalonSmsTemplate::whereNull('salon_id')
            ->where('template_type', 'system_event')
            ->where('event_type', 'exclusive_link')
            ->first();

        $customTemplates = SalonSmsTemplate::whereNull('salon_id')
            ->where('template_type', 'custom')
            ->where('is_active', true)
            ->where('event_type', 'exclusive_link')
            ->get();

        return response()->json([
            'data' => [
                'system_template' => $systemTemplate ? [
                    'id' => $systemTemplate->id,
                    'template' => $systemTemplate->template,
                    'is_active' => $systemTemplate->is_active,
                ] : null,
                'custom_templates' => $customTemplates->map(function($t){
                    return [
                        'id' => $t->id,
                        'title' => $t->title,
                        'template' => $t->template,
                        'is_active' => $t->is_active,
                    ];
                })->values(),
            ]
        ]);
    }
}
