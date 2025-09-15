<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use App\Models\User;

class ResetPasswordRequest extends FormRequest
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
        $passwordRules = ['required', 'confirmed', Password::min(8)->letters()->numbers()];

        if ($user && !$user->is_superadmin) {
            $passwordRules = ['required', 'confirmed', 'regex:/^[a-zA-Z0-9]+$/', 'min:4'];
        }

        return [
            'mobile' => 'required|regex:/^09\d{9}$/',
            'code' => 'required|digits:4',
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
            'code.required' => 'کد تایید الزامی است',
            'code.digits' => 'کد تایید باید ۴ رقمی باشد',
            'password.required' => 'رمز عبور الزامی است.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.letters' => 'رمز عبور باید حداقل شامل یک حرف انگلیسی باشد.',
            'password.numbers' => 'رمز عبور باید حداقل شامل یک عدد باشد.',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
        ];

        if ($user && !$user->is_superadmin) {
            $messages['password.regex'] = 'رمز عبور باید فقط شامل حروف انگلیسی و عدد باشد';
            $messages['password.min'] = 'رمز عبور باید حداقل ۴ کاراکتر باشد';
            unset($messages['password.letters'], $messages['password.numbers']);
        }

        return $messages;
    }
}
