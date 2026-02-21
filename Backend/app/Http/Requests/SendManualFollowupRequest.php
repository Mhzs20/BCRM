<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SendManualFollowupRequest extends FormRequest
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
            'customer_ids' => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['exists:customers,id'],
            'template_id' => ['required', 'exists:salon_sms_templates,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_ids.required' => 'انتخاب حداقل یک مشتری الزامی است.',
            'customer_ids.array' => 'لیست مشتریان باید به صورت آرایه باشد.',
            'customer_ids.min' => 'حداقل یک مشتری باید انتخاب شود.',
            'customer_ids.*.exists' => 'یکی از مشتریان انتخاب شده معتبر نیست.',
            'template_id.required' => 'انتخاب قالب پیامک الزامی است.',
            'template_id.exists' => 'قالب پیامک انتخاب شده معتبر نیست.',
        ];
    }
}
