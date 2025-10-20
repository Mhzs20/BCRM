<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Salon;
use Illuminate\Support\Facades\Auth;
use App\Rules\IranianPhoneNumber;

class PrepareAppointmentRequest extends FormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        $salon_parameter = $this->route('salon');
        $salonId = is_object($salon_parameter) ? $salon_parameter->id : $salon_parameter;

        $rules = [
            'customer_id' => [
                'nullable', 'sometimes',
                Rule::exists('customers', 'id')->where('salon_id', $salonId)->whereNull('deleted_at')
            ],
            'new_customer.name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'new_customer.phone_number' => ['required_without:customer_id', 'nullable', 'string', new IranianPhoneNumber],
            'new_customer.email' => ['nullable', 'email', 'max:255'],

            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => [
                'required', 'integer',
                Rule::exists('services', 'id')->where('salon_id', $salonId)->where('is_active', true)
            ],
            'staff_id' => [
                'required', 'integer',
                Rule::exists('salon_staff', 'id')->where('salon_id', $salonId)->where('is_active', true)
            ],
            'appointment_date' => ['required', 'jdate_format:Y-m-d', 'j_after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'total_duration' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'internal_notes' => ['nullable', 'string', 'max:1000'],
            'deposit_required' => ['nullable', 'boolean'],
            'deposit_paid' => ['nullable', 'boolean'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_payment_method' => ['nullable', 'string', Rule::in(['cash', 'card', 'online', 'other'])],
            'reminder_time' => ['nullable', 'integer', Rule::in([1, 2, 4, 6, 8, 12, 24, 48, 72])],
            'send_reminder_sms' => ['nullable', 'boolean'],
            'send_satisfaction_sms' => ['nullable', 'boolean'],
            'send_confirmation_sms' => ['nullable', 'boolean'],
            'confirmation_sms_template_id' => ['nullable', 'integer', 'exists:salon_sms_templates,id'],
            'reminder_sms_template_id' => ['nullable', 'integer', 'exists:salon_sms_templates,id'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'customer_id.exists' => 'مشتری انتخاب شده معتبر نیست.',
            'new_customer.name.required_without' => 'در صورت عدم انتخاب مشتری، وارد کردن نام مشتری جدید الزامی است.',
            'new_customer.phone_number.required_without' => 'در صورت عدم انتخاب مشتری، وارد کردن شماره تلفن مشتری جدید الزامی است.',
            'service_ids.required' => 'انتخاب حداقل یک سرویس الزامی است.',
            'service_ids.*.exists' => 'سرویس انتخاب شده معتبر یا فعال نیست.',
            'staff_id.required' => 'انتخاب پرسنل الزامی است.',
            'staff_id.exists' => 'پرسنل انتخاب شده معتبر یا فعال نیست.',
            'appointment_date.required' => 'وارد کردن تاریخ نوبت الزامی است.',
            'appointment_date.jdate_format' => 'فرمت تاریخ شمسی صحیح نیست.',
            'appointment_date.j_after_or_equal' => 'تاریخ نوبت نمی‌تواند در گذشته باشد.',
            'start_time.required' => 'وارد کردن ساعت شروع نوبت الزامی است.',
            'start_time.date_format' => 'فرمت ساعت شروع نوبت صحیح نیست.',
            'total_duration.required' => 'مدت زمان کل الزامی است.',
            'total_duration.min' => 'مدت زمان کل باید حداقل ۱ دقیقه باشد.',
        ];
    }
}
