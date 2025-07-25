<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        $salon_parameter = $this->route('salon');

        $salonId = is_object($salon_parameter) ? $salon_parameter->id : $salon_parameter;

        return [
            'service_ids' => ['sometimes', 'array', 'min:1'],
            'service_ids.*' => [
                'integer',
                // Use the correctly extracted $salonId
                Rule::exists('services', 'id')->where('salon_id', $salonId)->where('is_active', true)
            ],
            'staff_id' => [
                'sometimes', 'integer',
                Rule::exists('salon_staff', 'id')->where('salon_id', $salonId)->where('is_active', true)
            ],
            'appointment_date' => ['sometimes', 'jdate_format:Y-m-d', 'j_after_or_equal:today'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'string', Rule::in(['pending_confirmation', 'confirmed', 'cancelled', 'completed', 'no_show'])],
            'deposit_required' => ['sometimes', 'boolean'],
            'deposit_paid' => ['sometimes', 'boolean'],
            // Add other fields from Store request if they are updatable
            'internal_notes' => ['nullable', 'string', 'max:1000'],
            'send_sms_reminder' => ['sometimes', 'boolean'],
            'is_walk_in' => ['sometimes', 'boolean'],
            'deposit_amount' => ['sometimes', 'numeric', 'min:0'],
            'deposit_payment_method' => ['sometimes', 'string', Rule::in(['cash', 'card', 'online', 'other'])],
            'reminder_time' => ['sometimes', 'integer', Rule::in([2, 4, 6, 8, 12, 24, 48])],
            'send_reminder_sms' => ['sometimes', 'boolean'],
            'send_satisfaction_sms' => ['sometimes', 'boolean'],
            'total_price' => ['sometimes', 'numeric', 'min:0'],
        ];
    }

    public function messages()
    {
        return [
            'service_ids.array' => 'سرویس‌ها باید به صورت آرایه باشند.',
            'service_ids.min' => 'انتخاب حداقل یک سرویس الزامی است.',
            'service_ids.*.integer' => 'شناسه سرویس باید عدد صحیح باشد.',
            'service_ids.*.exists' => 'سرویس انتخاب شده معتبر یا فعال نیست.',
            'staff_id.integer' => 'شناسه پرسنل باید عدد صحیح باشد.',
            'staff_id.exists' => 'پرسنل انتخاب شده معتبر یا فعال نیست.',
            'appointment_date.jdate_format' => 'فرمت تاریخ شمسی صحیح نیست. لطفا از فرمت Y-m-d (مثال: 1404-03-18) استفاده کنید.',
            'appointment_date.j_after_or_equal' => 'تاریخ نوبت نمی‌تواند در گذشته باشد.',
            'start_time.date_format' => 'فرمت ساعت شروع نوبت صحیح نیست. لطفا از فرمت HH:MM (مثال: 09:00) استفاده کنید.',
            'notes.string' => 'یادداشت باید به صورت متنی باشد.',
            'notes.max' => 'یادداشت نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد.',
            'status.string' => 'وضعیت باید به صورت متنی باشد.',
            'status.in' => 'وضعیت انتخاب شده معتبر نیست.',
            'deposit_required.boolean' => 'مقدار "نیاز به بیعانه" باید صحیح یا غلط باشد.',
            'deposit_paid.boolean' => 'مقدار "بیعانه پرداخت شده" باید صحیح یا غلط باشد.',
            'internal_notes.string' => 'یادداشت داخلی باید به صورت متنی باشد.',
            'internal_notes.max' => 'یادداشت داخلی نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد.',
            'send_sms_reminder.boolean' => 'مقدار ارسال پیامک یادآوری باید صحیح یا غلط باشد.',
            'is_walk_in.boolean' => 'مقدار مشتری حضوری باید صحیح یا غلط باشد.',
            'deposit_amount.numeric' => 'مبلغ بیعانه باید به صورت عددی وارد شود.',
            'deposit_amount.min' => 'مبلغ بیعانه نمی‌تواند منفی باشد.',
            'deposit_payment_method.string' => 'روش پرداخت بیعانه باید به صورت متنی باشد.',
            'deposit_payment_method.in' => 'روش پرداخت بیعانه انتخاب شده معتبر نیست.',
            'reminder_time.integer' => 'زمان یادآوری باید عدد صحیح باشد.',
            'reminder_time.in' => 'زمان یادآوری انتخاب شده معتبر نیست.',
            'send_reminder_sms.boolean' => 'مقدار ارسال پیامک یادآوری باید صحیح یا غلط باشد.',
            'send_satisfaction_sms.boolean' => 'مقدار ارسال پیامک نظرسنجی باید صحیح یا غلط باشد.',
            'total_price.numeric' => 'مبلغ کل باید به صورت عددی وارد شود.',
            'total_price.min' => 'مبلغ کل نمی‌تواند منفی باشد.',
        ];
    }
}
