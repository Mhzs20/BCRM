<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportCustomersExcelRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'], // Max 5MB
        ];
    }
    public function messages()
    {
        return [
            'file.required' => 'فایل اکسل الزامی است.',
            'file.file' => 'فایل آپلود شده معتبر نیست.',
            'file.mimes' => 'فرمت فایل باید xlsx، xls یا csv باشد.',
            'file.max' => 'حداکثر حجم فایل می‌تواند ۵ مگابایت باشد.',
        ];
    }
}
