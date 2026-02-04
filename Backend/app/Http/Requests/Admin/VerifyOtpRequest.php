<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'mobile' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
            ],
            'otp_code' => [
                'required',
                'string',
                'digits:6',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'mobile.required' => 'شماره موبایل الزامی است.',
            'mobile.regex' => 'فرمت شماره موبایل صحیح نیست.',
            'otp_code.required' => 'کد تایید الزامی است.',
            'otp_code.digits' => 'کد تایید باید 6 رقم باشد.',
        ];
    }
}
