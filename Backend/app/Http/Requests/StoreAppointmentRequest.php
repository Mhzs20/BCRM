<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        $salonId = $this->route('salon');


        $rules = [
            'customer_id' => [
                'nullable', 'sometimes',
                Rule::exists('customers', 'id')->where('salon_id', $salonId)->whereNull('deleted_at')
            ],
            'new_customer.name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'new_customer.phone_number' => ['required_without:customer_id', 'nullable', 'string', 'max:20'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => [
                'required',
                Rule::exists('services', 'id')->where('salon_id', $salonId)->where('is_active', true)
            ],
            'staff_id' => [
                'required',
                Rule::exists('salon_staff', 'id')->where('salon_id', $salonId)->where('is_active', true)
            ],
            'appointment_date' => ['required', 'j_date_format:Y-m-d', 'j_after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'string', Rule::in(['pending_confirmation', 'confirmed', 'cancelled', 'completed', 'no_show'])],
            'deposit_required' => ['nullable', 'boolean'],
            'deposit_paid' => ['nullable', 'boolean'],
        ];

        if ($this->filled('new_customer.phone_number') && $salonId) {
            $rules['new_customer.phone_number'][] = Rule::unique('customers', 'phone_number')
                ->where('salon_id', $salonId)
                ->whereNull('deleted_at');
        }

        return $rules;
    }
    public function messages()
    {
        return [
            'appointment_date.j_date_format' => 'فرمت تاریخ شمسی صحیح نیست (مثال: 1404-03-18).',
            'appointment_date.j_after_or_equal' => 'تاریخ نوبت نمی‌تواند در گذشته باشد.',
            'start_time.required' => 'ساعت شروع نوبت الزامی است.',
            'start_time.date_format' => 'فرمت ساعت شروع نوبت صحیح نیست (مثال: 09:00).',
        ];
    }
}
