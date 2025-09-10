<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Salon;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        $salon = $this->route('salon');
        return $this->user()->id === $salon->user_id;
    }

    protected function prepareForValidation()
    {
        if ($this->has('schedules') && is_string($this->schedules)) {
            $this->merge(['schedules' => json_decode($this->schedules, true)]);
        }
    }

    public function rules(): array
    {
        $salon = $this->route('salon');
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20', new \App\Rules\IranianPhoneNumber(), Rule::unique('salon_staff', 'phone_number')->where('salon_id', $salon->id)],
            'specialty' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'gender' => ['nullable', 'in:male,female,other'],
            'address' => ['nullable', 'string', 'max:1000'],
            'schedules' => ['nullable', 'array'],
            'schedules.*' => ['sometimes', 'array'],
            'schedules.*.start' => ['required_with:schedules.*', 'date_format:H:i'],
            'schedules.*.end' => ['required_with:schedules.*', 'date_format:H:i', 'after:schedules.*.start'],
            'schedules.*.active' => ['sometimes', 'boolean'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer', Rule::exists('services', 'id')->where('salon_id', $salon->id)],
            'hire_date' => ['nullable', 'date'],
        ];
    }

    /**
     * Get the custom validation messages for the defined rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'وارد کردن نام کامل پرسنل الزامی است.',
            'full_name.string' => 'نام کامل باید به صورت متنی باشد.',
            'full_name.max' => 'نام کامل نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'phone_number.unique' => 'این شماره تلفن قبلاً برای پرسنل دیگری ثبت شده است.',
            'profile_image.image' => 'فایل آپلود شده باید یک تصویر معتبر باشد.',
            'profile_image.mimes' => 'فرمت تصویر باید یکی از موارد jpeg, png, jpg, gif, webp باشد.',
            'profile_image.max' => 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد.',
            'schedules.array' => 'برنامه زمانی باید به صورت آرایه ارسال شود.',
            'schedules.*.start.required_with' => 'ساعت شروع برای روزهای فعال الزامی است.',
            'schedules.*.start.date_format' => 'فرمت ساعت شروع (HH:MM) معتبر نیست.',
            'schedules.*.end.required_with' => 'ساعت پایان برای روزهای فعال الزامی است.',
            'schedules.*.end.date_format' => 'فرمت ساعت پایان (HH:MM) معتبر نیست.',
            'schedules.*.end.after' => 'ساعت پایان باید بعد از ساعت شروع باشد.',
            'service_ids.array' => 'سرویس‌ها باید به صورت آرایه‌ای از شناسه‌ها ارسال شوند.',
            'service_ids.*.integer' => 'شناسه هر سرویس باید یک عدد صحیح باشد.',
            'service_ids.*.exists' => 'سرویس انتخاب شده معتبر نیست یا به این سالن تعلق ندارد.',
        ];
    }
}
