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
                function ($attribute, $value, $fail) use ($salonId) {
                    if ($value != -1) {
                        $validator = validator(['staff_id' => $value], [
                            'staff_id' => Rule::exists('salon_staff', 'id')->where(function ($query) use ($salonId) {
                                $query->where('salon_id', $salonId)->where('is_active', true);
                            }),
                        ]);
                        if ($validator->fails()) {
                            $fail('پرسنل انتخاب شده معتبر یا فعال نیست.');
                        }
                    }
                },
            ],
            'service_ids' => ['nullable', 'array'], // Changed from required, removed min:1
            'service_ids.*' => [
                'integer', // Removed 'required'
                function ($attribute, $value, $fail) use ($salonId) {
                    if ($value != -1) {
                        $validator = validator(['service_id' => $value], [
                            'service_id' => Rule::exists('services', 'id')->where(function ($query) use ($salonId) {
                                $query->where('salon_id', $salonId)->where('is_active', true);
                            }),
                        ]);
                        if ($validator->fails()) {
                            $fail('سرویس انتخاب شده معتبر یا فعال نیست.');
                        }
                    }
                },
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
            'service_ids.array' => 'سرویس‌ها باید به صورت آرایه باشند.', // Added message for array type
            'staff_id.exists' => 'پرسنل انتخاب شده معتبر یا فعال نیست.', // This message is now handled by the custom rule
            'service_ids.*.exists' => 'سرویس انتخاب شده معتبر یا فعال نیست.', // This message is now handled by the custom rule
        ];
    }
}
