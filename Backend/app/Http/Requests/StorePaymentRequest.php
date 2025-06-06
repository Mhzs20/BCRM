<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && $this->route('salon') && Auth::user()->salons()->where('id', $this->route('salon')->id)->exists();
    }

    public function rules(): array
    {
        $salonId = $this->route('salon')->id;
        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where(function ($query) use ($salonId) {
                    $query->where('salon_id', $salonId);
                }),
            ],
            'appointment_id' => [
                'nullable',
                'integer',
                Rule::exists('appointments', 'id')->where(function ($query) use ($salonId) {
                    $query->where('salon_id', $salonId);
                }),
            ],
            'date' => 'required|string|size:10|regex:/^[1-4]\d{3}\/(0[1-9]|1[0-2])\/(0[1-9]|[12]\d|3[01])$/', // YYYY/MM/DD
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'انتخاب مشتری الزامی است.',
            'customer_id.exists' => 'مشتری انتخاب شده معتبر نیست یا به این سالن تعلق ندارد.',
            'appointment_id.exists' => 'نوبت انتخاب شده معتبر نیست یا به این سالن تعلق ندارد.',
            'date.required' => 'تاریخ پرداخت الزامی است.',
            'date.regex' => 'فرمت تاریخ پرداخت نامعتبر است (مثال: 1400/01/01).',
            'amount.required' => 'مبلغ پرداخت الزامی است.',
            'amount.numeric' => 'مبلغ باید عددی باشد.',
            'description.required' => 'توضیحات پرداخت الزامی است.',
            'description.max' => 'توضیحات نمی‌تواند بیشتر از 1000 کاراکتر باشد.',
        ];
    }
}
