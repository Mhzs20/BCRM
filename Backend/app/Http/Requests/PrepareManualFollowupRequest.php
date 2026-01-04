<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class PrepareManualFollowupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $salonId = $this->route('salon');
        return Auth::check() && Auth::user()->salons()->where('id', $salonId)->exists();
    }

    public function rules(): array
    {
        return [
            'customer_group_ids' => ['nullable', 'array'],
            'customer_group_ids.*' => ['exists:customer_groups,id'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['exists:services,id'],
            'days_since_last_visit' => ['required', 'integer', 'min:1', 'max:365'],
            'template_id' => ['required', 'exists:salon_sms_templates,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'days_since_last_visit.required' => 'تعداد روز از آخرین مراجعه الزامی است.',
            'days_since_last_visit.integer' => 'تعداد روز باید عدد صحیح باشد.',
            'days_since_last_visit.min' => 'حداقل یک روز باید انتخاب شود.',
            'days_since_last_visit.max' => 'حداکثر 365 روز قابل انتخاب است.',
            'template_id.required' => 'انتخاب قالب پیامک الزامی است.',
            'template_id.exists' => 'قالب پیامک انتخاب شده معتبر نیست.',
            'customer_group_ids.array' => 'گروه‌های مشتری باید به صورت آرایه باشد.',
            'customer_group_ids.*.exists' => 'یکی از گروه‌های مشتری معتبر نیست.',
            'service_ids.array' => 'سرویس‌ها باید به صورت آرایه باشد.',
            'service_ids.*.exists' => 'یکی از سرویس‌ها معتبر نیست.',
        ];
    }
}
