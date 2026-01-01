<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $salon = $this->route('salon');
        return Auth::check() && $salon && Auth::user()->salons()->where('id', $salon->id)->exists();
    }

    public function rules(): array
    {
        $salon = $this->route('salon');
        $salonId = $salon->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('services')->where(function ($query) use ($salonId) {
                return $query->where('salon_id', $salonId);
            })],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'staff_ids' => ['nullable', 'array'], // Added: Array of staff IDs
            'staff_ids.*' => ['integer', Rule::exists('salon_staff', 'id')->where(function ($query) use ($salonId) {
                return $query->where('salon_id', $salonId);
            })], // Validate staff IDs belong to the salon
            'repair_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام خدمت الزامی است.',
            'name.unique' => 'این نام خدمت قبلاً برای این سالن ثبت شده است.',
            'price.required' => 'قیمت خدمت الزامی است.',
            'price.numeric' => 'قیمت باید عددی باشد.',
            'price.min' => 'قیمت نمی‌تواند کمتر از ۰ باشد.',
            'staff_ids.array' => 'شناسه‌های پرسنل باید به صورت آرایه باشند.',
            'staff_ids.*.integer' => 'شناسه پرسنل باید عددی باشد.',
            'staff_ids.*.exists' => 'یک یا چند پرسنل انتخاب شده نامعتبر است یا به این سالن تعلق ندارد.',
            'repair_date.date' => 'تاریخ ترمیم باید یک تاریخ معتبر باشد.',
        ];
    }
}
