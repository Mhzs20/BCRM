<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Salon;
use App\Models\City;
use Illuminate\Validation\Rule;

class UpdateSalonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $salon = $this->route('salon');

        if ($salon instanceof Salon) {
            return $this->user()->can('update', $salon);
        }
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $salon = $this->route('salon');

        return [
            'name' => 'sometimes|required|string|max:255',
            'business_category_id' => 'sometimes|required|integer|exists:business_categories,id',
            'business_subcategory_id' => 'sometimes|nullable|integer|exists:business_subcategories,id',
            'province_id' => 'sometimes|required|integer|exists:provinces,id',
            'city_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('cities', 'id')->where(function ($query) use ($salon) {
                    $provinceIdToCompare = $this->input('province_id', $salon ? $salon->province_id : null);
                    if ($provinceIdToCompare) {
                        $query->where('province_id', $provinceIdToCompare);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }),
            ],
            'address' => 'sometimes|required|string|max:1000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone_number' => 'nullable|string|max:20',
            'instagram_url' => 'nullable|url|max:255',
            'website_url' => 'nullable|url|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'remove_image' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'نام سالن نمی‌تواند خالی باشد اگر ارسال شده است.',
            'business_category_id.required' => 'دسته‌بندی کسب و کار نمی‌تواند خالی باشد اگر ارسال شده است.',
            'business_subcategory_id.required' => 'زیرمجموعه کسب و کار نمی‌تواند خالی باشد اگر ارسال شده است.',
            'province_id.required' => 'استان نمی‌تواند خالی باشد اگر ارسال شده است.',
            'city_id.required' => 'شهر نمی‌تواند خالی باشد اگر ارسال شده است.',
            'city_id.exists' => 'شهر انتخاب شده معتبر نیست یا به استان انتخاب شده/فعلی تعلق ندارد.',
            'address.required' => 'آدرس نمی‌تواند خالی باشد اگر ارسال شده است.',
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
