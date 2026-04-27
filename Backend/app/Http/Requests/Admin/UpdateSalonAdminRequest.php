<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateSalonAdminRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $adminId = $this->route('admin');

        return [
            'first_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'last_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
            'permissions' => [
                'sometimes',
                'array',
            ],
            'permissions.*' => [
                'integer',
                'exists:permissions,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'نام الزامی است.',
            'last_name.required' => 'نام خانوادگی الزامی است.',
            'email.email' => 'فرمت ایمیل صحیح نیست.',
            'is_active.boolean' => 'وضعیت فعال/غیرفعال باید boolean باشد.',
            'permissions.array' => 'فرمت دسترسی‌ها صحیح نیست.',
            'permissions.*.exists' => 'دسترسی انتخاب شده معتبر نیست.',
        ];
    }
}
