<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Morilog\Jalali\Jalalian;

class GetAvailableSlotsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Prepare the data for validation by converting Jalali date to Gregorian.
     */
    protected function prepareForValidation()
    {
        if ($this->has('date')) {
            try {
                $jalaliDate = $this->input('date');
                $gregorianDate = Jalalian::fromFormat('Y-m-d', $jalaliDate)->toCarbon();
                $this->merge(['date' => $gregorianDate->format('Y-m-d')]);
            } catch (\Exception $e) {
                $this->merge(['date' => null]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // **-- کد اصلاح شده --**
        // پارامتر 'salon' از روت به صورت مستقیم به عنوان شناسه استفاده می‌شود
        $salonId = $this->route('salon');

        return [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'staff_id' => [
                'required', 'integer',
                Rule::exists('salon_staff', 'id')->where('salon_id', $salonId)
            ],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => [
                'required', 'integer',
                Rule::exists('services', 'id')->where('salon_id', $salonId)
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages()
    {
        return [
            'date.required' => 'وارد کردن تاریخ الزامی است.',
            'date.date' => 'فرمت تاریخ معتبر نیست.',
            'date.after_or_equal' => 'تاریخ نمی‌تواند در گذشته باشد.',
            'staff_id.required' => 'انتخاب پرسنل الزامی است.',
            'service_ids.required' => 'انتخاب حداقل یک سرویس الزامی است.',
        ];
    }
}
