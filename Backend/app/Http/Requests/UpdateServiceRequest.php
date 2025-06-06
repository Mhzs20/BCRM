<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\Service; // Make sure to import Service model.

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $salon = $this->route('salon');
        $service = $this->route('service'); // Get the service instance from the route.

        return Auth::check() &&
            $salon &&
            $service &&
            Auth::user()->salons()->where('id', $salon->id)->exists() &&
            $service->salon_id === $salon->id; // Ensure service belongs to the salon.
    }

    public function rules(): array
    {
        $salon = $this->route('salon');
        $salonId = $salon->id;
        $service = $this->route('service'); // Get the service instance.

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('services')->where(function ($query) use ($salonId) {
                return $query->where('salon_id', $salonId);
            })->ignore($service->id)], // Ignore current service ID for unique check.
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'duration_minutes' => ['sometimes', 'required', 'integer', 'min:1'], // Added
            'is_active' => ['sometimes', 'boolean'],
            'staff_ids' => ['sometimes', 'nullable', 'array'], // Added: Array of staff IDs
            'staff_ids.*' => ['integer', Rule::exists('salon_staff', 'id')->where(function ($query) use ($salonId) {
                return $query->where('salon_id', $salonId);
            })], // Validate staff IDs belong to the salon
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
            'duration_minutes.required' => 'مدت زمان خدمت الزامی است.',
            'duration_minutes.integer' => 'مدت زمان خدمت باید عددی باشد.',
            'duration_minutes.min' => 'مدت زمان خدمت باید حداقل ۱ دقیقه باشد.',
            'staff_ids.array' => 'شناسه‌های پرسنل باید به صورت آرایه باشند.',
            'staff_ids.*.integer' => 'شناسه پرسنل باید عددی باشد.',
            'staff_ids.*.exists' => 'یک یا چند پرسنل انتخاب شده نامعتبر است یا به این سالن تعلق ندارد.',
        ];
    }
}
