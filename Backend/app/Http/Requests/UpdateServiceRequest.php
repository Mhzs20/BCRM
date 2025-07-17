<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\Service;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $salon = $this->route('salon');
        $service = $this->route('service');

        return Auth::check() &&
            $salon &&
            $service &&
            Auth::user()->salons()->where('id', $salon->id)->exists() &&
            $service->salon_id === $salon->id;
    }

    public function rules(): array
    {
        {
            $salon = $this->route('salon');
            $salonId = $salon->id;
            $service = $this->route('service');

            return [
                // 'sometimes': validate only if present.
                // 'filled': validate only if present and not empty. This is key for partial updates.
                'name' => ['sometimes', 'filled', 'string', 'max:255', Rule::unique('services')->where(function ($query) use ($salonId) {
                    return $query->where('salon_id', $salonId);
                })->ignore($service->id)],

                // 'nullable': allows the field to be present but null.
                'description' => ['sometimes', 'nullable', 'string', 'max:1000'],

                'price' => ['sometimes', 'filled', 'numeric', 'min:0'],

                'duration_minutes' => ['sometimes', 'filled', 'integer', 'min:1'],

                // 'boolean' rule can be strict. 'in:true,false,1,0' is more flexible with form data.
                'is_active' => ['sometimes', 'boolean'],

                'staff_ids' => ['sometimes', 'nullable', 'array'],
                'staff_ids.*' => ['integer', Rule::exists('salon_staff', 'id')->where(function ($query) use ($salonId) {
                    return $query->where('salon_id', $salonId);
                })],
            ];
        }
    }

    public function messages(): array
    {
        return [
            'name.filled' => 'نام خدمت نمی‌تواند خالی باشد.',
            'name.unique' => 'این نام خدمت قبلاً برای این سالن ثبت شده است.',
            'price.filled' => 'قیمت خدمت نمی‌تواند خالی باشد.',
            'price.numeric' => 'قیمت باید عددی باشد.',
            'duration_minutes.filled' => 'مدت زمان خدمت نمی‌تواند خالی باشد.',
            'duration_minutes.integer' => 'مدت زمان خدمت باید عددی باشد.',
            'is_active.in' => 'مقدار فیلد فعال‌سازی نامعتبر است.',
            'staff_ids.array' => 'شناسه‌های پرسنل باید به صورت آرایه باشند.',
            'staff_ids.*.exists' => 'یک یا چند پرسنل انتخاب شده نامعتبر است.',
        ];
    }
}
