<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'avatar' => 'nullable|image|max:2048',
            'business_name' => 'sometimes|string|max:255',
            'business_category_id' => 'sometimes|exists:business_categories,id',
            'business_subcategory_id' => 'sometimes|exists:business_subcategories,id',
            'province_id' => 'sometimes|exists:provinces,id',
            'city_id' => 'sometimes|exists:cities,id',
        ];
    }

    /**
     *messages
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'نام الزامی است',
            'password.required' => 'رمز عبور الزامی است',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد',
            'avatar.image' => 'فایل آپلود شده باید تصویر باشد',
            'avatar.max' => 'حداکثر حجم تصویر ۲ مگابایت است',
            'business_category_id.exists' => 'دسته‌بندی کسب و کار انتخاب شده معتبر نیست.',
            'business_subcategory_id.exists' => 'زیرمجموعه کسب و کار انتخاب شده معتبر نیست.',
            'province_id.exists' => 'استان انتخاب شده معتبر نیست.',
            'city_id.exists' => 'شهر انتخاب شده معتبر نیست.',
        ];
    }
}
