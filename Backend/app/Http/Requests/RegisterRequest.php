<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
            'mobile' => [
                'required',
                'regex:/^09\d{9}$/',
                Rule::unique('users', 'mobile')->where(function ($query) {
                    return $query->whereNotNull('password');
                })
            ],
            'password' => [
                'required',
                'regex:/^[a-zA-Z0-9]+$/',
                'min:4',
            ],
            'referral_code' => [
                'nullable',
                'string',
                'exists:users,referral_code',
            ],
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
            'mobile.unique' => 'این شماره موبایل قبلا ثبت شده است',
            'password.required' => 'رمز عبور الزامی است',
            'password.regex' => 'رمز عبور باید فقط شامل حروف انگلیسی و عدد باشد',
            'password.min' => 'رمز عبور باید حداقل ۴ کاراکتر باشد',
            'referral_code.exists' => 'کد دعوت وارد شده معتبر نیست',
        ];
    }
}