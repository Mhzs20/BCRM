<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateFollowupSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $salon = $this->route('salon');
        $salonId = is_object($salon) ? $salon->getKey() : $salon;
        return Auth::check() && Auth::user()->salons()->where('id', $salonId)->exists();
    }

    public function rules(): array
    {
        return [
            'template_id' => ['nullable', 'exists:salon_sms_templates,id'],
            'customer_group_ids' => ['nullable', 'array'],
            'customer_group_ids.*.is_active' => ['boolean'],
            'customer_group_ids.*.days_since_last_visit' => ['integer', 'min:1', 'max:365'],
            'customer_group_ids.*.check_frequency_days' => ['integer', 'min:1', 'max:90'],
        ];
    }

    public function messages(): array
    {
        return [
            'template_id.exists' => 'قالب پیامک انتخاب شده معتبر نیست.',
            'customer_group_ids.array' => 'گروه‌های مشتری باید به صورت آرایه باشد.',
            'customer_group_ids.*.days_since_last_visit.integer' => 'تعداد روز از آخرین مراجعه باید عدد صحیح باشد.',
            'customer_group_ids.*.days_since_last_visit.min' => 'حداقل یک روز باید انتخاب شود.',
            'customer_group_ids.*.days_since_last_visit.max' => 'حداکثر 365 روز قابل انتخاب است.',
            'customer_group_ids.*.check_frequency_days.integer' => 'فرکانس بررسی باید عدد صحیح باشد.',
            'customer_group_ids.*.check_frequency_days.min' => 'حداقل یک روز برای فرکانس بررسی باید انتخاب شود.',
            'customer_group_ids.*.check_frequency_days.max' => 'حداکثر 90 روز برای فرکانس بررسی قابل انتخاب است.',
        ];
    }
}
