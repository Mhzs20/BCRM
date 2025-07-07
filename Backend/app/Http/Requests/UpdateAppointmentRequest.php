<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UpdateAppointmentRequest extends FormRequest
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
            'service_ids' => ['sometimes', 'array', 'min:1'],
            'service_ids.*' => [
                'integer',
                // Use the correctly extracted $salonId
                Rule::exists('services', 'id')->where('salon_id', $salonId)->where('is_active', true)
            ],
            'staff_id' => [
                'sometimes', 'integer',
                Rule::exists('salon_staff', 'id')->where('salon_id', $salonId)->where('is_active', true)
            ],
            'appointment_date' => ['sometimes', 'jdate_format:Y-m-d', 'j_after_or_equal:today'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'string', Rule::in(['pending_confirmation', 'confirmed', 'cancelled', 'completed', 'no_show'])],
            'deposit_required' => ['sometimes', 'boolean'],
            'deposit_paid' => ['sometimes', 'boolean'],
            // Add other fields from Store request if they are updatable
            'internal_notes' => ['nullable', 'string', 'max:1000'],
            'send_sms_reminder' => ['sometimes', 'boolean'],
            'is_walk_in' => ['sometimes', 'boolean'],
            'deposit_amount' => ['sometimes', 'numeric', 'min:0'],
            'deposit_payment_method' => ['sometimes', 'string', Rule::in(['cash', 'card', 'online', 'other'])],
            'reminder_time' => ['sometimes', 'integer', Rule::in([2, 4, 6, 8])],
            'send_reminder_sms' => ['sometimes', 'boolean'],
            'send_satisfaction_sms' => ['sometimes', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'appointment_date.j_date_format' => 'فرمت تاریخ شمسی صحیح نیست (مثال: 1404-03-18).',
            'appointment_date.j_after_or_equal' => 'تاریخ نوبت نمی‌تواند یک روز گذشته باشد.',
            'start_time.date_format' => 'فرمت ساعت شروع نوبت صحیح نیست (مثال: 09:00).',
        ];
    }
}
