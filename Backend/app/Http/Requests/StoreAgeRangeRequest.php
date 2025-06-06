<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgeRangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        $salon = $this->route('salon');
        return $this->user()->can('update', $salon);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'وارد کردن نام بازه سنی الزامی است.',
            'name.string' => 'نام بازه سنی باید به صورت متنی باشد.',
            'name.max' => 'نام بازه سنی نمی‌تواند بیشتر از 255 کاراکتر باشد.',
        ];
    }
}
