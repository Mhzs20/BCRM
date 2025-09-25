<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // If user fields are sent flat, nest them under 'user'
        if (!$this->has('user') && ($this->has('current_password') || $this->has('new_password') || $this->has('name') || $this->has('email') || $this->has('gender') || $this->has('date_of_birth'))) {
            $userData = [];
            $userFields = ['name', 'email', 'current_password', 'new_password', 'new_password_confirmation', 'gender', 'date_of_birth'];
            foreach ($userFields as $field) {
                if ($this->has($field)) {
                    $userData[$field] = $this->input($field);
                }
            }
            $this->merge(['user' => $userData]);
        }

        // Ensure business_subcategory_ids are correctly merged into the salon array
        // This handles cases where it's sent as salon[business_subcategory_ids][]
        // For PUT/PATCH requests with form-data, input() might be empty.
        // We explicitly check the request's input for the nested array.
        $salonInput = $this->input('salon', []);
        $businessSubcategoryIds = $this->input('salon.business_subcategory_ids');

        if (!empty($businessSubcategoryIds) && is_array($businessSubcategoryIds)) {
            $this->merge([
                'salon' => array_merge($salonInput, [
                    'business_subcategory_ids' => $businessSubcategoryIds,
                ]),
            ]);
        }
    }

    public function rules(): array
    {
        $user = $this->user();
        $salon = $user->activeSalon;
        $provinceId = $this->input('salon.province_id', $salon ? $salon->province_id : null);

        return [
            // User fields
            'user.name' => 'sometimes|string|max:255',
            'user.email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'user.current_password' => 'nullable|required_with:user.new_password|string',
            'user.new_password' => $this->user()->is_superadmin 
                ? ['nullable', 'string', 'confirmed', Password::min(8)->letters()->numbers()]
                : ['nullable', 'string', 'confirmed', 'regex:/^[a-zA-Z0-9]+$/', 'min:4'],
            'user.gender' => 'sometimes|nullable|in:male,female,other',
            'user.date_of_birth' => 'sometimes|nullable|string', // Will be parsed as Jalali date in controller

            // Salon fields
            'salon.name' => 'sometimes|string|min:3|max:255',
            'salon.business_category_id' => 'sometimes|integer|exists:business_categories,id',
            'salon.business_subcategory_ids' => 'sometimes|nullable|array',
            'salon.business_subcategory_ids.*' => 'integer|exists:business_subcategories,id',
            'salon.province_id' => 'sometimes|integer|exists:provinces,id',
            'salon.city_id' => $provinceId ? ['sometimes', 'integer', Rule::exists('cities', 'id')->where('province_id', $provinceId)] : 'sometimes|integer|exists:cities,id',
            'salon.address' => 'sometimes|string|max:1000',
            'salon.image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'salon.remove_image' => 'sometimes|boolean',
            'salon.instagram' => 'sometimes|nullable|string|max:255',
            'salon.telegram' => 'sometimes|nullable|string|max:255',
            'salon.website' => 'sometimes|nullable|string|max:255',
            'salon.latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'salon.longitude' => 'sometimes|nullable|numeric|between:-180,180',
            'salon.support_phone_number' => 'sometimes|nullable|string|max:20',
            'salon.bio' => 'sometimes|nullable|string|max:1000',
            'salon.whatsapp' => 'sometimes|nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        $user = $this->user();
        $messages = [
            'user.name.string' => 'نام باید رشته باشد',
            'user.name.max' => 'نام نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد',
            'user.email.email' => 'ایمیل باید فرمت صحیح داشته باشد',
            'user.email.max' => 'ایمیل نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد',
            'user.email.unique' => 'این ایمیل قبلا ثبت شده است',
            'user.current_password.required_with' => 'رمز عبور فعلی الزامی است',
            'user.new_password.string' => 'رمز عبور جدید باید رشته باشد',
            'user.new_password.confirmed' => 'تکرار رمز عبور جدید مطابقت ندارد',
            'user.new_password.min' => 'رمز عبور جدید باید حداقل ۸ کاراکتر باشد',
            'user.new_password.letters' => 'رمز عبور جدید باید حداقل شامل یک حرف انگلیسی باشد',
            'user.new_password.numbers' => 'رمز عبور جدید باید حداقل شامل یک عدد باشد',
            'user.gender.in' => 'جنسیت باید یکی از مقادیر male, female یا other باشد',
            'user.date_of_birth.string' => 'تاریخ تولد باید رشته باشد',
            // Salon messages
            'salon.name.string' => 'نام سالن باید رشته باشد',
            'salon.name.min' => 'نام سالن باید حداقل ۳ کاراکتر باشد',
            'salon.name.max' => 'نام سالن نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد',
            'salon.business_category_id.integer' => 'دسته‌بندی کسب و کار باید عدد صحیح باشد',
            'salon.business_category_id.exists' => 'دسته‌بندی کسب و کار انتخاب شده معتبر نیست',
            'salon.business_subcategory_ids.array' => 'زیر دسته‌بندی‌ها باید آرایه باشند',
            'salon.business_subcategory_ids.*.integer' => 'هر زیر دسته‌بندی باید عدد صحیح باشد',
            'salon.business_subcategory_ids.*.exists' => 'زیر دسته‌بندی انتخاب شده معتبر نیست',
            'salon.province_id.integer' => 'استان باید عدد صحیح باشد',
            'salon.province_id.exists' => 'استان انتخاب شده معتبر نیست',
            'salon.city_id.integer' => 'شهر باید عدد صحیح باشد',
            'salon.city_id.exists' => 'شهر انتخاب شده معتبر نیست',
            'salon.address.string' => 'آدرس باید رشته باشد',
            'salon.address.max' => 'آدرس نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد',
            'salon.image.image' => 'تصویر سالن باید از نوع تصویر باشد',
            'salon.image.mimes' => 'فرمت تصویر سالن باید jpeg, png, jpg, gif یا webp باشد',
            'salon.image.max' => 'حداکثر حجم تصویر سالن می‌تواند ۲ مگابایت باشد',
            'salon.remove_image.boolean' => 'حذف تصویر باید مقدار بولی باشد',
            'salon.instagram.string' => 'اینستاگرام باید رشته باشد',
            'salon.instagram.max' => 'اینستاگرام نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد',
            'salon.telegram.string' => 'تلگرام باید رشته باشد',
            'salon.telegram.max' => 'تلگرام نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد',
            'salon.website.string' => 'وبسایت باید رشته باشد',
            'salon.website.max' => 'وبسایت نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد',
            'salon.latitude.numeric' => 'عرض جغرافیایی باید عدد باشد',
            'salon.latitude.between' => 'عرض جغرافیایی باید بین -۹۰ و ۹۰ باشد',
            'salon.longitude.numeric' => 'طول جغرافیایی باید عدد باشد',
            'salon.longitude.between' => 'طول جغرافیایی باید بین -۱۸۰ و ۱۸۰ باشد',
            'salon.support_phone_number.string' => 'شماره تلفن پشتیبانی باید رشته باشد',
            'salon.support_phone_number.max' => 'شماره تلفن پشتیبانی نمی‌تواند بیشتر از ۲۰ کاراکتر باشد',
            'salon.bio.string' => 'بیو باید رشته باشد',
            'salon.bio.max' => 'بیو نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد',
            'salon.whatsapp.string' => 'واتساپ باید رشته باشد',
            'salon.whatsapp.max' => 'واتساپ نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد',
        ];

        if (!$user->is_superadmin) {
            $messages['user.new_password.regex'] = 'رمز عبور جدید باید فقط شامل حروف انگلیسی و عدد باشد';
            $messages['user.new_password.min'] = 'رمز عبور جدید باید حداقل ۴ کاراکتر باشد';
            unset($messages['user.new_password.letters'], $messages['user.new_password.numbers']);
        }

        return $messages;
    }
}
