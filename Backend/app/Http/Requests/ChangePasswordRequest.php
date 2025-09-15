<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ChangePasswordRequest extends FormRequest
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
        $passwordRules = ['required', 'min:8', 'confirmed'];

        if (!$user->is_superadmin) {
            $passwordRules = ['required', 'regex:/^[a-zA-Z0-9]+$/', 'min:4', 'confirmed'];
        }

        return [
            'current_password' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!Hash::check($value, Auth::user()->password)) {
                        $fail('رمز عبور فعلی نادرست است.');
                    }
                },
            ],
            'new_password' => $passwordRules,
            'new_password_confirmation' => 'required',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        $user = $this->user();
        $messages = [
            'current_password.required' => 'رمز عبور فعلی الزامی است',
            'new_password.required' => 'رمز عبور جدید الزامی است',
            'new_password.min' => 'رمز عبور جدید باید حداقل ۸ کاراکتر باشد',
            'new_password.confirmed' => 'تکرار رمز عبور جدید مطابقت ندارد',
            'new_password_confirmation.required' => 'تکرار رمز عبور جدید الزامی است',
        ];

        if (!$user->is_superadmin) {
            $messages['new_password.regex'] = 'رمز عبور جدید باید فقط شامل حروف انگلیسی و عدد باشد';
            $messages['new_password.min'] = 'رمز عبور جدید باید حداقل ۴ کاراکتر باشد';
        }

        return $messages;
    }
}
