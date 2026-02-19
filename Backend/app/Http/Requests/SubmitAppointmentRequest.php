<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SubmitAppointmentRequest extends FormRequest
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
            // Either pending_appointment_id (for create) OR appointment_id (for update)
            'pending_appointment_id' => [
                'required_without:appointment_id', 
                'integer', 
                function ($attribute, $value, $fail) use ($salonId) {
                    if (!$value) return;
                    $exists = \App\Models\PendingAppointment::where('id', $value)
                        ->where('salon_id', $salonId)
                        ->where('expires_at', '>', now())
                        ->exists();
                    if (!$exists) {
                        $fail('نوبت موقت یافت نشد یا منقضی شده است.');
                    }
                },
            ],
            'appointment_id' => [
                'required_without:pending_appointment_id',
                'integer',
                Rule::exists('appointments', 'id')->where('salon_id', $salonId)
            ],
            
            // Fields for update mode
            'staff_id' => [
                'sometimes', 'integer',
                Rule::exists('salon_staff', 'id')->where('salon_id', $salonId)->where('is_active', true)
            ],
            'service_ids' => ['sometimes', 'array', 'min:1'],
            'service_ids.*' => [
                'integer',
                Rule::exists('services', 'id')->where('salon_id', $salonId)->where('is_active', true)
            ],
            'appointment_date' => ['sometimes', 'string'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
            'total_duration' => ['sometimes', 'integer', 'min:1'],
            'total_price' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', Rule::in(['pending_confirmation', 'confirmed', 'cancelled', 'completed', 'no_show'])],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'internal_note' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'deposit_required' => ['sometimes', 'boolean'],
            'deposit_paid' => ['sometimes', 'boolean'],
            'deposit_amount' => ['sometimes', 'numeric', 'min:0'],
            'deposit_payment_method' => ['sometimes', 'string', Rule::in(['cash', 'card', 'online', 'other'])],
            'reminder_time' => ['sometimes', 'integer', Rule::in([1, 2, 4, 6, 8, 12, 24, 48, 72])],
            'send_reminder_sms' => ['sometimes', 'boolean'],
            'send_satisfaction_sms' => ['sometimes', 'boolean'],
            'send_confirmation_sms' => ['sometimes', 'boolean'],
            'confirmation_sms_template_id' => ['sometimes', 'nullable', 'integer', 'exists:salon_sms_templates,id'],
            'reminder_sms_template_id' => ['sometimes', 'nullable', 'integer', 'exists:salon_sms_templates,id'],
        ];
    }

    public function messages()
    {
        return [
            'pending_appointment_id.required_without' => 'باید یکی از فیلدهای pending_appointment_id یا appointment_id ارسال شود.',
            'pending_appointment_id.integer' => 'شناسه نوبت موقت باید عدد صحیح باشد.',
            'appointment_id.required_without' => 'باید یکی از فیلدهای pending_appointment_id یا appointment_id ارسال شود.',
            'appointment_id.integer' => 'شناسه نوبت باید عدد صحیح باشد.',
            'appointment_id.exists' => 'نوبت یافت نشد.',
            'staff_id.integer' => 'شناسه پرسنل باید عدد صحیح باشد.',
            'staff_id.exists' => 'پرسنل انتخاب شده معتبر یا فعال نیست.',
            'service_ids.array' => 'سرویس‌ها باید به صورت آرایه باشند.',
            'service_ids.min' => 'انتخاب حداقل یک سرویس الزامی است.',
            'service_ids.*.integer' => 'شناسه سرویس باید عدد صحیح باشد.',
            'service_ids.*.exists' => 'سرویس انتخاب شده معتبر یا فعال نیست.',
        ];
    }
}
