<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterSmsCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'min_age' => 'nullable|integer|min:0',
            'max_age' => 'nullable|integer|min:0|gte:min_age',
            'profession_id' => 'nullable|array',
            'profession_id.*' => 'integer|exists:professions,id',
            'customer_group_id' => 'nullable|array',
            'customer_group_id.*' => 'integer|exists:customer_groups,id',
            'min_payment' => 'nullable|numeric|min:0',
            'max_payment' => 'nullable|numeric|min:0|gte:min_payment',
            'how_introduced_id' => 'nullable|array',
            'how_introduced_id.*' => 'integer|exists:how_introduceds,id',
            'min_appointments' => 'nullable|integer|min:0',
            'max_appointments' => 'nullable|integer|min:0|gte:min_appointments',
            'sms_template_id' => 'nullable|integer|exists:salon_sms_templates,id',
            'message' => 'nullable|string|max:1000',
        ];
    }
}
