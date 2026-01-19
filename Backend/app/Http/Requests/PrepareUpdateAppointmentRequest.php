<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class PrepareUpdateAppointmentRequest extends FormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        $salon_parameter = $this->route('salon');
        $salonId = is_object($salon_parameter) ? $salon_parameter->id : $salon_parameter;

        return [
            'appointment_id' => [
                'required',
                'integer',
                Rule::exists('appointments', 'id')->where('salon_id', $salonId)
            ],
            'service_ids' => ['sometimes', 'array', 'min:1'],
            'service_ids.*' => [
                'integer',
                Rule::exists('services', 'id')->where('salon_id', $salonId)->where('is_active', true)
            ],
            'staff_id' => [
                'sometimes', 'integer',
                Rule::exists('salon_staff', 'id')->where('salon_id', $salonId)->where('is_active', true)
            ],
            'appointment_date' => ['sometimes', 'jdate_format:Y-m-d', 'j_after_or_equal:today'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'total_duration' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'string', Rule::in(['pending_confirmation', 'confirmed', 'cancelled', 'completed', 'no_show'])],
            'deposit_required' => ['sometimes', 'boolean'],
            'deposit_paid' => ['sometimes', 'boolean'],
            'internal_notes' => ['nullable', 'string', 'max:1000'],
            'send_sms_reminder' => ['sometimes', 'boolean'],
            'is_walk_in' => ['sometimes', 'boolean'],
            'deposit_amount' => ['sometimes', 'numeric', 'min:0'],
            'deposit_payment_method' => ['sometimes', 'string', Rule::in(['cash', 'card', 'online', 'other'])],
            'reminder_time' => ['sometimes', 'integer', Rule::in([1, 2, 4, 6, 8, 12, 24, 48, 72])],
            'send_reminder_sms' => ['sometimes', 'boolean'],
            'send_satisfaction_sms' => ['sometimes', 'boolean'],
            'send_confirmation_sms' => ['sometimes', 'boolean'],
            'confirmation_sms_template_id' => ['sometimes', 'nullable', 'integer', 'exists:salon_sms_templates,id'],
            'reminder_sms_template_id' => ['sometimes', 'nullable', 'integer', 'exists:salon_sms_templates,id'],
            'total_price' => ['sometimes', 'numeric', 'min:0'],
        ];
    }

    public function messages()
    {
        return [
            'appointment_id.required' => 'شناسه نوبت الزامی است.',
            'appointment_id.exists' => 'نوبت یافت نشد.',
            'service_ids.required' => 'حداقل یک سرویس باید انتخاب شود.',
            'service_ids.array' => 'سرویس‌ها باید به صورت آرایه باشند.',
            'service_ids.min' => 'حداقل یک سرویس باید انتخاب شود.',
            'service_ids.*.exists' => 'یکی از سرویس‌های انتخاب شده معتبر نیست.',
            'staff_id.required' => 'انتخاب کارمند الزامی است.',
            'staff_id.exists' => 'کارمند انتخاب شده معتبر نیست.',
            'appointment_date.required' => 'تاریخ نوبت الزامی است.',
            'appointment_date.jdate_format' => 'فرمت تاریخ باید به صورت Y-m-d باشد.',
            'appointment_date.j_after_or_equal' => 'تاریخ نوبت نمی‌تواند قبل از امروز باشد.',
            'start_time.required' => 'ساعت شروع نوبت الزامی است.',
            'start_time.date_format' => 'فرمت ساعت شروع باید H:i باشد.',
            'total_duration.required' => 'مدت زمان کل نوبت الزامی است.',
            'total_duration.integer' => 'مدت زمان باید عدد صحیح باشد.',
            'total_duration.min' => 'مدت زمان حداقل باید ۱ دقیقه باشد.',
            'total_duration.max' => 'مدت زمان نمی‌تواند بیش از ۱۴۴۰ دقیقه (۲۴ ساعت) باشد.',
        ];
    }
}
