<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SubmitUpdateAppointmentRequest extends FormRequest
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
            'pending_update_id' => [
                'required', 
                'integer', 
                function ($attribute, $value, $fail) {
                    if (!$value) return;
                    $exists = \App\Models\PendingAppointmentUpdate::where('id', $value)
                        ->where('salon_id', $salonId)
                        ->where('expires_at', '>', now())
                        ->exists();
                    if (!$exists) {
                        $fail('تغییرات موقت نوبت یافت نشد یا منقضی شده است.');
                    }
                },
            ],
            'confirm_conflict' => ['sometimes', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'pending_update_id.required' => 'شناسه تغییرات موقت الزامی است.',
            'pending_update_id.integer' => 'شناسه تغییرات موقت باید عدد باشد.',
        ];
    }
}
