<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
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
        ];
    }
}
