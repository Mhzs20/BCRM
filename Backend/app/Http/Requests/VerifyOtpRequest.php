<?php

namespace App\Http\Requests;

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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mobile' => 'required|regex:/^09\d{9}$/',
            'code' => 'required|digits:4',
            'remember' => 'boolean|nullable',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'mobile.required' => 'شماره موبایل الزامی است',
            'mobile.regex' => 'فرمت شماره موبایل صحیح نیست',
            'code.required' => 'کد تایید الزامی است',
            'code.digits' => 'کد تایید باید ۴ رقمی باشد',
            'remember.boolean' => 'مقدار فیلد مرا به خاطر بسپار باید از نوع بولین باشد',
        ];
    }
}