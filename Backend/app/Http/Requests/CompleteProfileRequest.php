<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'image' => 'nullable|image|max:2048',
            'business_name' => 'required|string|max:255',
            'business_category_id' => 'required|exists:business_categories,id',
            'business_subcategory_id' => 'nullable|exists:business_subcategories,id',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'address' => 'required|string|max:1000',
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
            'image.image' => 'فایل آپلود شده باید تصویر باشد',
            'image.max' => 'حداکثر حجم تصویر ۲ مگابایت است',
            'business_name.required' => 'وارد کردن نام کسب و کار (سالن) الزامی است.',
            'business_category_id.required' => 'انتخاب دسته‌بندی کسب و کار الزامی است.',
            'business_category_id.exists' => 'دسته‌بندی کسب و کار انتخاب شده معتبر نیست.',
            'business_subcategory_id.exists' => 'زیرمجموعه کسب و کار انتخاب شده معتبر نیست.',
            'province_id.required' => 'انتخاب استان الزامی است.',
            'province_id.exists' => 'استان انتخاب شده معتبر نیست.',
            'city_id.required' => 'انتخاب شهر الزامی است.',
            'city_id.exists' => 'شهر انتخاب شده معتبر نیست.',
            'address.required' => 'وارد کردن آدرس سالن الزامی است.',
            'address.max' => 'آدرس سالن نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if ($this->hasFile('image')) {
            $this->merge([
                'avatar' => $this->file('image'),
            ]);
        }
    }
}
