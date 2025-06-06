<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Salon;
use App\Models\City;
use Illuminate\Validation\Rule;

class StoreSalonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'business_category_id' => 'required|integer|exists:business_categories,id',
            'business_subcategory_id' => 'nullable|integer|exists:business_subcategories,id',
            'province_id' => 'required|integer|exists:provinces,id',
            'city_id' => [
                'required',
                'integer',
                Rule::exists('cities', 'id')->where(function ($query) {
                    $query->where('province_id', $this->input('province_id'));
                }),
            ],
            'address' => 'required|string|max:1000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone_number' => 'nullable|string|max:20',
            'instagram_url' => 'nullable|url|max:255',
            'website_url' => 'nullable|url|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'نام سالن الزامی است.',
            'name.max' => 'نام سالن نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد.',
            'business_category_id.required' => 'انتخاب دسته‌بندی کسب و کار الزامی است.',
            'business_category_id.exists' => 'دسته‌بندی کسب و کار انتخاب شده معتبر نیست.',
            'business_subcategory_id.exists' => 'زیرمجموعه کسب و کار انتخاب شده معتبر نیست.',
            'province_id.required' => 'انتخاب استان الزامی است.',
            'province_id.exists' => 'استان انتخاب شده معتبر نیست.',
            'city_id.required' => 'انتخاب شهر الزامی است.',
            'city_id.exists' => 'شهر انتخاب شده معتبر نیست یا به استان انتخاب شده تعلق ندارد.',
            'address.required' => 'آدرس الزامی است.',
            'address.max' => 'آدرس نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد.',
            'latitude.numeric' => 'عرض جغرافیایی باید یک عدد باشد.',
            'latitude.between' => 'عرض جغرافیایی باید بین -90 و 90 باشد.',
            'longitude.numeric' => 'طول جغرافیایی باید یک عدد باشد.',
            'longitude.between' => 'طول جغرافیایی باید بین -180 و 180 باشد.',
            'phone_number.max' => 'شماره تماس نمی‌تواند بیشتر از ۲۰ کاراکتر باشد.',
            'instagram_url.url' => 'آدرس اینستاگرام معتبر نیست.',
            'website_url.url' => 'آدرس وب‌سایت معتبر نیست.',
            'image.image' => 'فایل آپلود شده باید تصویر باشد.',
            'image.mimes' => 'فرمت تصویر باید jpeg, png, jpg یا gif باشد.',
            'image.max' => 'حداکثر حجم تصویر ۲ مگابایت است.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('business_subcategory_id') && empty($this->business_subcategory_id)) {
            $this->merge([
                'business_subcategory_id' => null,
            ]);
        }
    }
}
