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
        // Check if any input related to salon.business_subcategory_ids is present
        // This handles cases where it's sent as salon[business_subcategory_ids][]
        if ($this->hasAny(['salon.business_subcategory_ids', 'salon.business_subcategory_ids.0'])) {
            $this->merge([
                'salon' => array_merge($this->input('salon', []), [
                    'business_subcategory_ids' => $this->input('salon.business_subcategory_ids', []),
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
            'user.new_password' => ['nullable', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
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
}
