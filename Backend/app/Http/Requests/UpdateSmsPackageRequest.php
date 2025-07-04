<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateSmsPackageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Assuming only superadmin can update SMS packages
        // You might need to adjust this based on your actual authorization logic
        return Auth::check() && Auth::user()->is_superadmin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'sms_count' => 'sometimes|integer|min:0',
            'price' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'نام بسته باید متنی باشد.',
            'name.max' => 'نام بسته نمی‌تواند بیشتر از 255 کاراکتر باشد.',
            'sms_count.integer' => 'تعداد پیامک باید عددی باشد.',
            'sms_count.min' => 'تعداد پیامک نمی‌تواند کمتر از 0 باشد.',
            'price.numeric' => 'قیمت باید عددی باشد.',
            'price.min' => 'قیمت نمی‌تواند کمتر از 0 باشد.',
            'is_active.boolean' => 'وضعیت فعال بودن باید بله یا خیر باشد.',
        ];
    }
}
