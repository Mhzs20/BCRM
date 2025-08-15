<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $salon = $this->route('salon');
        return $salon && $this->user()->can('update', $salon);
    }

    public function rules(): array
    {
        $salon = $this->route('salon');
        $provinceId = $this->input('province_id', $salon->province_id);

        return [
            'name'                      => 'sometimes|string|min:3|max:255',
            'business_category_id'      => 'sometimes|integer|exists:business_categories,id',
            'business_subcategory_ids'   => 'sometimes|nullable|array',
            'business_subcategory_ids.*' => 'exists:business_subcategories,id',
            'province_id'               => 'sometimes|integer|exists:provinces,id',
            'city_id'                   => ['sometimes', 'integer', Rule::exists('cities', 'id')->where('province_id', $provinceId)],
            'address'                   => 'sometimes|string|max:1000',
            'image'                     => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'remove_image'              => 'sometimes|boolean',
            'instagram'                 => 'sometimes|nullable|string|max:255',
            'telegram'                  => 'sometimes|nullable|string|max:255',
            'website'                   => 'sometimes|nullable|string|max:255',
            'latitude'                  => 'sometimes|nullable|numeric|between:-90,90',
            'longitude'                 => 'sometimes|nullable|numeric|between:-180,180',
            'support_phone_number'      => 'sometimes|nullable|string|max:20',
            'bio'                       => 'sometimes|nullable|string|max:1000',
            'whatsapp'                  => 'sometimes|nullable|string|max:255',
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
