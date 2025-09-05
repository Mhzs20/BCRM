<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalonSmsTemplate;
use App\Models\SmsTemplateCategory;
use Illuminate\Http\Request;

class SmsTemplateController extends Controller
{
    private const ALLOWED_EVENT_TYPES = [
        'appointment_confirmation',
        'appointment_reminder',
        'appointment_cancellation',
        'appointment_modification',
        'birthday_greeting',
        'service_specific_notes',
    ];

    public function index()
    {
        // System templates (global, event based)
        $existing = SalonSmsTemplate::whereNull('salon_id')->where('template_type', 'system_event')->get()->keyBy('event_type');
        $systemTemplates = collect();
        foreach (self::ALLOWED_EVENT_TYPES as $et) {
            $model = $existing->get($et);
            $systemTemplates->push((object) [
                'event_type' => $et,
                'id' => $model?->id,
                'template' => $model?->template ?? $this->defaultText($et),
                'is_active' => $model?->is_active ?? true,
            ]);
        }

        $categories = SmsTemplateCategory::whereNull('salon_id')
            ->with(['templates' => function($q){ $q->whereNull('salon_id')->where('template_type','custom')->orderByDesc('id'); }])
            ->orderBy('name')
            ->get();

        return view('admin.sms-templates.index', [
            'systemTemplates' => $systemTemplates,
            'categories' => $categories,
        ]);
    }

    public function systemUpdate(Request $request)
    {
        $data = $request->validate([
            'templates' => 'required|array',
            'templates.*.template' => 'required|string|max:1000',
            'templates.*.is_active' => 'nullable|boolean'
        ]);
        foreach ($data['templates'] as $eventType => $tpl) {
            if (!in_array($eventType, self::ALLOWED_EVENT_TYPES)) continue;
            SalonSmsTemplate::updateOrCreate([
                'salon_id' => null,
                'event_type' => $eventType,
                'template_type' => 'system_event'
            ], [
                'template' => $tpl['template'],
                'is_active' => $tpl['is_active'] ?? true,
            ]);
        }
        return redirect()->route('admin.sms-templates.index')->with('success', 'قالب‌های سیستمی ذخیره شد.');
    }

    public function create()
    {
        $categories = SmsTemplateCategory::whereNull('salon_id')->orderBy('name')->get();
        return view('admin.sms-templates.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'template' => 'required|string|max:1000',
            'category_id' => 'nullable|integer|exists:sms_template_categories,id',
            'is_active' => 'nullable|boolean'
        ]);

        if ($request->category_id) {
            $cat = SmsTemplateCategory::find($request->category_id);
            if ($cat->salon_id !== null) {
                return back()->withErrors(['category_id' => 'دسته معتبر نیست.']);
            }
        }

        SalonSmsTemplate::create([
            'salon_id' => null,
            'category_id' => $request->category_id,
            'title' => $request->title,
            'template' => $request->template,
            'is_active' => $request->boolean('is_active', true),
            'template_type' => 'custom'
        ]);
        return redirect()->route('admin.sms-templates.index')->with('success', 'قالب سفارشی ایجاد شد.');
    }

    public function edit(SalonSmsTemplate $smsTemplate)
    {
        if ($smsTemplate->template_type !== 'custom' || !is_null($smsTemplate->salon_id) === false) {
            return redirect()->route('admin.sms-templates.index')->with('error', 'ویرایش این قالب مجاز نیست.');
        }
        $categories = SmsTemplateCategory::whereNull('salon_id')->orderBy('name')->get();
        return view('admin.sms-templates.edit', compact('smsTemplate','categories'));
    }

    public function update(Request $request, SalonSmsTemplate $smsTemplate)
    {
        if ($smsTemplate->template_type !== 'custom') {
            return redirect()->route('admin.sms-templates.index')->with('error', 'ویرایش این قالب مجاز نیست.');
        }
        $request->validate([
            'title' => 'required|string|max:150',
            'template' => 'required|string|max:1000',
            'category_id' => 'nullable|integer|exists:sms_template_categories,id',
            'is_active' => 'nullable|boolean'
        ]);
        if ($request->category_id) {
            $cat = SmsTemplateCategory::find($request->category_id);
            if ($cat->salon_id !== null) {
                return back()->withErrors(['category_id' => 'دسته معتبر نیست.']);
            }
        }
        $smsTemplate->update([
            'category_id' => $request->category_id,
            'title' => $request->title,
            'template' => $request->template,
            'is_active' => $request->boolean('is_active', true),
        ]);
        return redirect()->route('admin.sms-templates.index')->with('success', 'قالب بروزرسانی شد.');
    }

    public function destroy(SalonSmsTemplate $smsTemplate)
    {
        if ($smsTemplate->template_type !== 'custom') {
            return redirect()->route('admin.sms-templates.index')->with('error', 'حذف این قالب مجاز نیست.');
        }
        $smsTemplate->delete();
        return redirect()->route('admin.sms-templates.index')->with('success', 'قالب حذف شد.');
    }

    private function defaultText(string $eventType): string
    {
        return match($eventType) {
            'appointment_confirmation' => 'مشتری گرامی {{customer_name}}، نوبت شما در سالن {{salon_name}} برای تاریخ {{appointment_date}} ساعت {{appointment_time}} ثبت شد.',
            'appointment_reminder' => 'یادآوری: فردا {{appointment_date}} ساعت {{appointment_time}} در سالن {{salon_name}}.',
            'appointment_cancellation' => 'نوبت شما در {{salon_name}} برای {{appointment_date}} لغو شد.',
            'appointment_modification' => 'نوبت شما در {{salon_name}} تغییر یافت: {{appointment_date}} {{appointment_time}}.',
            'birthday_greeting' => 'تولدتان مبارک {{customer_name}} عزیز از طرف {{salon_name}}.',
            'service_specific_notes' => 'یادآوری خدمت {{service_name}} در تاریخ {{appointment_date}}: {{service_specific_notes}}',
            default => ''
        };
    }
}

