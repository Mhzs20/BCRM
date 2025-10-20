<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SubmitAppointmentRequest extends FormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        return [
            'pending_appointment_id' => [
                'required', 'integer', 
                function ($attribute, $value, $fail) {
                    $exists = \App\Models\PendingAppointment::where('id', $value)
                        ->where('salon_id', $this->route('salon'))
                        ->where('expires_at', '>', now())
                        ->exists();
                    if (!$exists) {
                        $fail('نوبت موقت یافت نشد یا منقضی شده است.');
                    }
                },
            ],
        ];
    }

    public function messages()
    {
        return [
            'pending_appointment_id.required' => 'شناسه نوبت موقت الزامی است.',
            'pending_appointment_id.integer' => 'شناسه نوبت موقت باید عدد صحیح باشد.',
            'pending_appointment_id.exists' => 'نوبت موقت یافت نشد یا منقضی شده است.',
        ];
    }
}
