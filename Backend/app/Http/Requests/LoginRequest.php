<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class LoginRequest extends FormRequest
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
        $user = $this->user();
        $passwordRules = 'required|string|min:8';

        if ($user && !$user->is_superadmin) {
            $passwordRules = 'required|regex:/^[a-zA-Z0-9]+$/|min:4';
        }

        return [
            'mobile' => 'required|regex:/^09\d{9}$/',
            'password' => $passwordRules,
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        $user = User::where('mobile', $this->input('mobile'))->first();
        $messages = [
            'mobile.required' => 'شماره موبایل الزامی است',
            'mobile.regex' => 'فرمت شماره موبایل صحیح نیست',
            'password.required' => 'رمز عبور الزامی است',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد',
        ];

        if ($user && !$user->is_superadmin) {
            $messages['password.regex'] = 'رمز عبور باید فقط شامل حروف انگلیسی و عدد باشد';
            $messages['password.min'] = 'رمز عبور باید حداقل ۴ کاراکتر باشد';
        }

        return $messages;
    }
}