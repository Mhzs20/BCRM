<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Staff;
use App\Models\Salon;

class UpdateStaffRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Salon $salon */
        $salon = $this->route('salon');
        /** @var Staff $staff */
        $staff = $this->route('staff');


        return $this->user()->id === $salon->user_id && $staff->salon_id === $salon->id;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        if ($this->has('working_hours') && is_string($this->working_hours)) {
            $this->merge([
                'working_hours' => json_decode($this->working_hours, true)
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        /** @var Salon $salon */
        $salon = $this->route('salon');
        /** @var Staff $staff */
        $staff = $this->route('staff');

        return [

            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone_number' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('salon_staff', 'phone_number')->where('salon_id', $salon->id)->ignore($staff->id)
            ],
            'specialty' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'profile_image' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'remove_profile_image' => ['sometimes', 'boolean'],

            'working_hours' => ['sometimes', 'nullable', 'array'],
            'working_hours.*.start' => ['required', 'date_format:H:i'],
            'working_hours.*.end' => ['required', 'date_format:H:i', 'after:working_hours.*.start'],
            'working_hours.*.active' => ['required', 'boolean'],

            'service_ids' => ['sometimes', 'nullable', 'array'],
            'service_ids.*' => ['integer', Rule::exists('services', 'id')->where('salon_id', $salon->id)],
        ];
    }
}
