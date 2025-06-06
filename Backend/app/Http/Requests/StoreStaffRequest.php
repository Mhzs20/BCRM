<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Salon;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Salon $salon */
        $salon = $this->route('salon');
        return $this->user()->id === $salon->user_id;
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if ($this->has('working_hours') && is_string($this->working_hours)) {
            $this->merge([
                'working_hours' => json_decode($this->working_hours, true)
            ]);
        }
    }

    public function rules(): array
    {
        /** @var Salon $salon */
        $salon = $this->route('salon');

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20', Rule::unique('salon_staff', 'phone_number')->where('salon_id', $salon->id)],
            'specialty' => ['nullable', 'string', 'max:255'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],

            'working_hours' => ['nullable', 'array'],
            'working_hours.*.start' => ['required', 'date_format:H:i'],
            'working_hours.*.end' => ['required', 'date_format:H:i', 'after:working_hours.*.start'],
            'working_hours.*.active' => ['required', 'boolean'],

            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer', Rule::exists('services', 'id')->where('salon_id', $salon->id)],
        ];
    }
}
