<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth;
class CompleteProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $user = $this->user();
        $passwordRules = ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()];

        if (!$user->is_superadmin) {
            $passwordRules = ['required', 'string', 'confirmed', 'regex:/^[a-zA-Z0-9]+$/', 'min:4'];
        }

        return [
            'name' => 'required|string|max:255',
            'password' => $passwordRules,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'business_name' => 'required|string|max:255',
            'business_category_id' => 'required|exists:business_categories,id',
            'business_subcategory_ids' => 'nullable|array',
            'business_subcategory_ids.*' => 'integer|exists:business_subcategories,id',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'address' => 'required|string|max:1000',
            'support_phone_number'      => 'nullable|string|max:20',
            'bio'                       => 'nullable|string|max:1000',
            'instagram'                 => 'nullable|string|max:255',
            'telegram'                  => 'nullable|string|max:255',
            'website'                   => 'nullable|string|max:255',
            'latitude'                  => 'nullable|numeric|between:-90,90',
            'longitude'                 => 'nullable|numeric|between:-180,180',
            'image'                     => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'whatsapp'                  => 'nullable|string|max:255',
            'referral_code'             => 'nullable|string|exists:users,referral_code',
        ];
    }

    /**
     *messages
     *
     * @return array
     */
    public function messages()
    {
        $user = $this->user();
        $messages = [
            'name.required' => 'نام الزامی است',
            'password.required' => 'رمز عبور الزامی است.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.letters' => 'رمز عبور باید حداقل شامل یک حرف انگلیسی باشد.',
            'password.numbers' => 'رمز عبور باید حداقل شامل یک عدد باشد.',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
            'avatar.image' => 'فایل آپلود شده برای آواتار باید از نوع تصویر باشد.',
            'avatar.mimes' => 'فرمت تصویر آواتار باید jpeg, png, jpg, gif یا webp باشد.',
            'avatar.max' => 'حداکثر حجم تصویر آواتار می‌تواند ۲ مگابایت باشد.',
            'image.image' => 'فایل آپلود شده برای تصویر سالن باید از نوع تصویر باشد.',
            'image.mimes' => 'فرمت تصویر سالن باید jpeg, png, jpg, gif یا webp باشد.',
            'image.max' => 'حداکثر حجم تصویر سالن می‌تواند ۲ مگابایت باشد.',
            'business_name.required' => 'وارد کردن نام کسب و کار (سالن) الزامی است.',
            'business_category_id.required' => 'انتخاب دسته‌بندی کسب و کار الزامی است.',
            'business_category_id.exists' => 'دسته‌بندی کسب و کار انتخاب شده معتبر نیست.',
            'province_id.required' => 'انتخاب استان الزامی است.',
            'province_id.exists' => 'استان انتخاب شده معتبر نیست.',
            'city_id.required' => 'انتخاب شهر الزامی است.',
            'city_id.exists' => 'شهر انتخاب شده معتبر نیست.',
            'address.required' => 'وارد کردن آدرس سالن الزامی است.',
            'address.max' => 'آدرس سالن نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد.',
            'referral_code.exists' => 'کد دعوت وارد شده معتبر نیست.',
        ];

        if (!$user->is_superadmin) {
            $messages['password.regex'] = 'رمز عبور باید فقط شامل حروف انگلیسی و عدد باشد';
            $messages['password.min'] = 'رمز عبور باید حداقل ۴ کاراکتر باشد';
            unset($messages['password.letters'], $messages['password.numbers']);
        }

        return $messages;
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    // The prepareForValidation method is no longer needed as we use 'avatar' directly.
}
