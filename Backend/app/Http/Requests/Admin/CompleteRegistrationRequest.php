<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CompleteRegistrationRequest extends FormRequest
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
            'temp_token' => [
                'required',
                'string',
                'exists:admin_otp_verifications,temp_token',
            ],
            'first_name' => [
                'required',
                'string',
                'max:255',
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->numbers(),
            ],
            'permissions' => [
                'required',
                'array',
                'min:1',
            ],
            'permissions.*' => [
                'required',
                'integer',
                'exists:permissions,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'temp_token.required' => 'توکن موقت الزامی است.',
            'temp_token.exists' => 'توکن موقت نامعتبر است.',
            'first_name.required' => 'نام الزامی است.',
            'last_name.required' => 'نام خانوادگی الزامی است.',
            'email.email' => 'فرمت ایمیل صحیح نیست.',
            'password.required' => 'رمز عبور الزامی است.',
            'password.confirmed' => 'تایید رمز عبور مطابقت ندارد.',
            'password.min' => 'رمز عبور باید حداقل 8 کاراکتر باشد.',
            'permissions.required' => 'حداقل یک دسترسی باید انتخاب شود.',
            'permissions.array' => 'فرمت دسترسی‌ها صحیح نیست.',
            'permissions.*.exists' => 'دسترسی انتخاب شده معتبر نیست.',
        ];
    }
}
