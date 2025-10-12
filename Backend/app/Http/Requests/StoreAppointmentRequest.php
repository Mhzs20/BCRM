<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Salon;
use Illuminate\Support\Facades\Auth;
use App\Rules\IranianPhoneNumber;

class StoreAppointmentRequest extends FormRequest
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
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'string', Rule::in(['pending_confirmation', 'confirmed', 'cancelled', 'completed', 'no_show'])],
            'internal_notes' => ['nullable', 'string', 'max:1000'],
            'send_sms_reminder' => ['nullable', 'boolean'],
            'is_walk_in' => ['nullable', 'boolean'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_payment_method' => ['nullable', 'string', Rule::in(['cash', 'card', 'online', 'other'])],
            'reminder_time' => ['nullable', 'integer', Rule::in([2, 4, 6, 8, 12, 24, 48])],
            'send_reminder_sms' => ['nullable', 'boolean'],
            'send_satisfaction_sms' => ['nullable', 'boolean'],
            'send_confirmation_sms' => ['nullable', 'boolean'],
            'confirmation_sms_template_id' => ['nullable', 'integer', 'exists:salon_sms_templates,id'],
            'reminder_sms_template_id' => ['nullable', 'integer', 'exists:salon_sms_templates,id'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'total_duration' => ['required', 'integer', 'min:1'], // Added total_duration validation
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'customer_id.exists' => 'مشتری انتخاب شده معتبر نیست.',
            'new_customer.name.required_without' => 'در صورت عدم انتخاب مشتری، وارد کردن نام مشتری جدید الزامی است.',
            'new_customer.name.string' => 'نام مشتری جدید باید به صورت متنی باشد.',
            'new_customer.name.max' => 'نام مشتری جدید نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد.',
            'new_customer.phone_number.required_without' => 'در صورت عدم انتخاب مشتری، وارد کردن شماره تلفن مشتری جدید الزامی است.',
            'new_customer.phone_number.string' => 'شماره تلفن مشتری جدید باید به صورت متنی باشد.',
            'new_customer.phone_number.max' => 'شماره تلفن مشتری جدید نمی‌تواند بیشتر از ۲۰ کاراکتر باشد.',
            'new_customer.phone_number.unique' => 'این شماره تلفن قبلا برای مشتری دیگری در این سالن ثبت شده است.',
            'service_ids.required' => 'انتخاب حداقل یک سرویس الزامی است.',
            'service_ids.array' => 'سرویس‌ها باید به صورت آرایه باشند.',
            'service_ids.min' => 'انتخاب حداقل یک سرویس الزامی است.',
            'service_ids.*.required' => 'سرویس انتخاب شده الزامی است.',
            'service_ids.*.integer' => 'شناسه سرویس باید عدد صحیح باشد.',
            'service_ids.*.exists' => 'سرویس انتخاب شده معتبر یا فعال نیست.',
            'staff_id.required' => 'انتخاب پرسنل الزامی است.',
            'staff_id.integer' => 'شناسه پرسنل باید عدد صحیح باشد.',
            'staff_id.exists' => 'پرسنل انتخاب شده معتبر یا فعال نیست.',
            'appointment_date.required' => 'وارد کردن تاریخ نوبت الزامی است.',
            'appointment_date.jdate_format' => 'فرمت تاریخ شمسی صحیح نیست. لطفا از فرمت Y-m-d (مثال: 1404-03-18) استفاده کنید.',
            'appointment_date.j_after_or_equal' => 'تاریخ نوبت نمی‌تواند در گذشته باشد.',
            'start_time.required' => 'وارد کردن ساعت شروع نوبت الزامی است.',
            'start_time.date_format' => 'فرمت ساعت شروع نوبت صحیح نیست. لطفا از فرمت HH:MM (مثال: 09:00) استفاده کنید.',
            'notes.string' => 'یادداشت باید به صورت متنی باشد.',
            'notes.max' => 'یادداشت نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد.',
            'status.string' => 'وضعیت باید به صورت متنی باشد.',
            'status.in' => 'وضعیت انتخاب شده معتبر نیست.',
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
            'send_confirmation_sms.boolean' => 'مقدار ارسال پیامک ثبت نوبت باید صحیح یا غلط باشد.',
            'confirmation_sms_template_id.integer' => 'شناسه تمپلیت پیامک ثبت نوبت باید عددی باشد.',
            'confirmation_sms_template_id.exists' => 'تمپلیت پیامک ثبت نوبت انتخاب شده معتبر نیست.',
            'reminder_sms_template_id.integer' => 'شناسه تمپلیت پیامک یادآوری باید عددی باشد.',
            'reminder_sms_template_id.exists' => 'تمپلیت پیامک یادآوری انتخاب شده معتبر نیست.',
            'total_price.numeric' => 'مبلغ کل باید به صورت عددی وارد شود.',
            'total_price.min' => 'مبلغ کل نمی‌تواند منفی باشد.',
            'total_duration.required' => 'مدت زمان کل الزامی است.',
            'total_duration.integer' => 'مدت زمان کل باید عددی باشد.',
            'total_duration.min' => 'مدت زمان کل باید حداقل ۱ دقیقه باشد.',
        ];
    }
}
