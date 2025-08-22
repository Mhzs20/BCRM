<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreSalonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'name'                      => 'required|string|max:255',
            'business_category_id'      => 'required|integer|exists:business_categories,id',
            'business_subcategory_ids'   => 'nullable|array',
            'business_subcategory_ids.*' => 'integer|exists:business_subcategories,id',
            'province_id'               => 'required|integer|exists:provinces,id',
            'city_id'                   => ['required', 'integer', Rule::exists('cities', 'id')->where('province_id', $this->input('province_id'))],
            'address'                   => 'required|string|max:1000',
            'image'                     => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'instagram'                 => 'nullable|string|max:255',
            'telegram'                  => 'nullable|string|max:255',
            'website'                   => 'nullable|string|max:255',
            'latitude'                  => 'nullable|numeric|between:-90,90',
            'longitude'                 => 'nullable|numeric|between:-180,180',
            'support_phone_number'      => 'nullable|string|max:20',
            'bio'                       => 'nullable|string|max:1000',
            'whatsapp'                  => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'وارد کردن نام سالن الزامی است.',
            'business_category_id.required' => 'انتخاب دسته‌بندی کسب و کار الزامی است.',
            'province_id.required' => 'انتخاب استان الزامی است.',
            'city_id.required' => 'انتخاب شهر الزامی است.',
            'city_id.exists' => 'شهر انتخاب شده معتبر نیست یا به استان انتخابی تعلق ندارد.',
            'address.required' => 'وارد کردن آدرس الزامی است.',
            'mobile.regex' => 'فرمت شماره موبایل صحیح نیست. (مثال: 09123456789)',
            'website.url' => 'فرمت آدرس وب‌سایت صحیح نیست.',
            'image.image' => 'فایل آپلود شده باید از نوع تصویر باشد.',
            'image.max' => 'حداکثر حجم تصویر می‌تواند ۲ مگابایت باشد.',
        ];
    }
}
