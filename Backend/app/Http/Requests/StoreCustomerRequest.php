<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Morilog\Jalali\Jalalian;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $salon = $this->route('salon');
        return Auth::check() && $salon && Auth::user()->salons()->where('id', $salon->id)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $salon = $this->route('salon');
        $salonId = $salon->id;

        return [
            'name' => 'required|string|max:255',
            'phone_number' => [
                'required',
                'string',
                'max:20',
                    Rule::unique('customers', 'phone_number')->where(function ($query) use ($salonId) {
                        return $query->where('salon_id', $salonId)
                            ->whereNull('deleted_at');
                    })
            ],
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'birth_date' => 'nullable|jdate_format:Y/m/d',
            'gender' => 'nullable|in:male,female,other',
            'emergency_contact' => ['nullable', 'string', 'max:20', new \App\Rules\IranianPhoneNumber()],
            'address' => 'nullable|string',
            'notes' => 'nullable|string',

            'how_introduced_id' => [
                'nullable',
                'integer',
                Rule::exists('how_introduceds', 'id')->where('salon_id', $salonId),
            ],
            'profession_id' => [
                'nullable',
                'integer',
                Rule::exists('professions', 'id')->where('salon_id', $salonId),
            ],
            'age_range_id' => [
                'nullable',
                'integer',
                Rule::exists('age_ranges', 'id')->where('salon_id', $salonId),
            ],
            'customer_group_id' => [
                'nullable',
                'integer',
                Rule::exists('customer_groups', 'id')->where('salon_id', $salonId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'وارد کردن نام مشتری الزامی است.',
            'name.string' => 'نام مشتری باید به صورت متنی باشد.',
            'name.max' => 'نام مشتری نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد.',
            'phone_number.required' => 'وارد کردن شماره تماس مشتری الزامی است.',
            'phone_number.string' => 'شماره تماس باید به صورت متنی باشد.',
            'phone_number.max' => 'شماره تماس نمی‌تواند بیشتر از ۲۰ کاراکتر باشد.',
            'phone_number.unique' => 'این شماره تماس قبلاً برای مشتری دیگری در این سالن ثبت شده است.',
            'profile_image.image' => 'فایل انتخابی برای تصویر پروفایل باید یک تصویر باشد.',
            'profile_image.mimes' => 'فرمت تصویر پروفایل معتبر نیست. فرمت‌های مجاز: jpeg, png, jpg, gif, webp.',
            'profile_image.max' => 'حجم تصویر پروفایل نباید بیشتر از 2 مگابایت باشد.',
            'birth_date.jdate_format' => 'فرمت تاریخ تولد نامعتبر است. لطفاً از فرمت YYYY/MM/DD استفاده کنید (مثال: 1370/01/01).',
            'gender.in' => 'جنسیت انتخاب شده معتبر نیست.',
            'emergency_contact.string' => 'شماره تماس اضطراری باید به صورت متنی باشد.',
            'emergency_contact.max' => 'شماره تماس اضطراری نمی‌تواند بیشتر از ۲۰ کاراکتر باشد.',
            'address.string' => 'آدرس باید به صورت متنی باشد.',
            'notes.string' => 'یادداشت باید به صورت متنی باشد.',
            'how_introduced_id.integer' => 'نحوه آشنایی انتخاب شده نامعتبر است.',
            'how_introduced_id.exists' => 'نحوه آشنایی انتخاب شده در این سالن تعریف نشده است.',
            'profession_id.integer' => 'شغل انتخاب شده نامعتبر است.',
            'profession_id.exists' => 'شغل انتخاب شده در این سالن تعریف نشده است.',
            'age_range_id.integer' => 'بازه سنی انتخاب شده نامعتبر است.',
            'age_range_id.exists' => 'بازه سنی انتخاب شده در این سالن تعریف نشده است.',
            'customer_group_id.integer' => 'گروه مشتری انتخاب شده نامعتبر است.',
            'customer_group_id.exists' => 'گروه مشتری انتخاب شده در این سالن تعریف نشده است.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * This method is called before the validation rules are applied.
     * You can use it to modify the data if needed.
     */
    protected function prepareForValidation()
    {

        if ($this->has('birth_date') && $this->birth_date !== null) {
            $this->merge([
                'birth_date' => $this->convertToEnglishNumbers($this->birth_date),
            ]);
        }
    }

    /**
     * Get the validated data from the request.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function validated($key = null, $default = null)
    {
        $validatedData = parent::validated($key, $default);


        if (isset($validatedData['birth_date']) && $validatedData['birth_date'] !== null) {
            try {
                $jalaliDate = Jalalian::fromFormat('Y/m/d', $validatedData['birth_date']);
                $validatedData['birth_date'] = $jalaliDate->toCarbon('Asia/Tehran')->toDateString();
            } catch (\Exception $e) {
                $validatedData['birth_date'] = null;
            }
        }

        return $validatedData;
    }

    /**
     * Helper function to convert Persian/Arabic numbers in a string to English numbers.
     *
     * @param string $string
     * @return string
     */
    private function convertToEnglishNumbers(string $string): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace($persian, $english, str_replace($arabic, $english, $string));
    }
}
