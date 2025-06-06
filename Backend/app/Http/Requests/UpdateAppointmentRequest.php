<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        $salonId = $this->route('salon_id');

        return [
            'service_ids' => ['sometimes', 'required', 'array', 'min:1'],
            'service_ids.*' => [
                'sometimes', 'required',
                Rule::exists('services', 'id')->where('salon_id', $salonId)->where('is_active', true)
            ],
            'staff_id' => [
                'sometimes', 'required',
                Rule::exists('salon_staff', 'id')->where('salon_id', $salonId)->where('is_active', true)
            ],
            'appointment_date' => ['sometimes', 'required', 'j_date_format:Y-m-d', 'j_after_or_equal:today'],
            'start_time' => ['sometimes', 'required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'required', 'string', Rule::in(['pending_confirmation', 'confirmed', 'cancelled', 'completed', 'no_show'])],
            'deposit_required' => ['sometimes', 'boolean'],
            'deposit_paid' => ['sometimes', 'boolean'],
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
