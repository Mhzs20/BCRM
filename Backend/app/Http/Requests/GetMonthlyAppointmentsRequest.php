<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetMonthlyAppointmentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Assuming authorization logic will be handled by policies or middleware
        // For now, allow all authenticated users to make this request.
        return true; 
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:1300', 'max:1500'], // Assuming Jalali years
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Merge route parameters into the request data so they can be validated
        $this->merge([
            'year' => $this->route('year'),
            'month' => $this->route('month'),
        ]);
    }
}
