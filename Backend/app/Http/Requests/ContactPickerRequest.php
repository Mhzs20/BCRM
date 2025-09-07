<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Rules\IranianPhoneNumber;
use Morilog\Jalali\Jalalian;

class ContactPickerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $salon = $this->route('salon');
        return Auth::check() && 
               $salon && 
               Auth::user()->salons()->where('id', $salon->id)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $salon = $this->route('salon');
        $salonId = $salon ? $salon->id : null;

        return [
            'contacts' => ['required', 'array', 'min:1', 'max:100'], // Limit to 100 contacts per batch
            'contacts.*.name' => ['required', 'string', 'max:255'],
            'contacts.*.phone_number' => [
                'required', 
                'string', 
                'max:20',
                new IranianPhoneNumber() // استفاده از قانون ولیدیشن موجود
            ],
            'contacts.*.profile_image' => ['nullable', 'string'], // For contact picker, might receive base64 or URL
            'contacts.*.birth_date' => ['nullable', 'jdate_format:Y/m/d'], // فرمت تاریخ شمسی مشابه سیستم
            'contacts.*.gender' => ['nullable', 'in:male,female,other'],
            'contacts.*.emergency_contact' => [
                'nullable', 
                'string', 
                'max:20',
                new IranianPhoneNumber() // اعتبارسنجی شماره تماس اضطراری
            ],
            'contacts.*.address' => ['nullable', 'string', 'max:500'], // محدود کردن طول آدرس
            'contacts.*.notes' => ['nullable', 'string', 'max:1000'], // محدود کردن طول یادداشت
            
            // Foreign key validations - اگر سالن موجود باشد
            'contacts.*.how_introduced_id' => [
                'nullable',
                'integer',
                $salonId ? Rule::exists('how_introduceds', 'id')->where('salon_id', $salonId) : 'integer',
            ],
            'contacts.*.profession_id' => [
                'nullable',
                'integer',
                $salonId ? Rule::exists('professions', 'id')->where('salon_id', $salonId) : 'integer',
            ],
            'contacts.*.age_range_id' => [
                'nullable',
                'integer',
                $salonId ? Rule::exists('age_ranges', 'id')->where('salon_id', $salonId) : 'integer',
            ],
            'contacts.*.customer_group_id' => [
                'nullable',
                'integer',
                $salonId ? Rule::exists('customer_groups', 'id')->where('salon_id', $salonId) : 'integer',
            ],
            'contacts.*.city_id' => [
                'nullable',
                'integer',
                Rule::exists('cities', 'id'),
            ],
            
            'update_existing' => ['nullable', 'boolean'], // Whether to update existing customers
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'contacts.required' => 'لیست مخاطبین الزامی است.',
            'contacts.array' => 'لیست مخاطبین باید به صورت آرایه باشد.',
            'contacts.min' => 'حداقل یک مخاطب باید انتخاب شود.',
            'contacts.max' => 'حداکثر 100 مخاطب در هر بار قابل انتخاب است.',
            
            'contacts.*.name.required' => 'نام مخاطب الزامی است.',
            'contacts.*.name.string' => 'نام مخاطب باید به صورت متنی باشد.',
            'contacts.*.name.max' => 'نام مخاطب نمی‌تواند بیشتر از 255 کاراکتر باشد.',
            
            'contacts.*.phone_number.required' => 'شماره تلفن مخاطب الزامی است.',
            'contacts.*.phone_number.string' => 'شماره تلفن باید به صورت متنی باشد.',
            'contacts.*.phone_number.max' => 'شماره تلفن نمی‌تواند بیشتر از 20 کاراکتر باشد.',
            
            'contacts.*.profile_image.string' => 'تصویر پروفایل باید به صورت متنی باشد.',
            
            'contacts.*.birth_date.jdate_format' => 'فرمت تاریخ تولد نامعتبر است. لطفاً از فرمت Y/m/d استفاده کنید (مثال: 1370/01/01).',
            
            'contacts.*.gender.in' => 'جنسیت انتخاب شده معتبر نیست. مقادیر مجاز: مرد، زن، سایر',
            
            'contacts.*.emergency_contact.string' => 'شماره تماس اضطراری باید به صورت متنی باشد.',
            'contacts.*.emergency_contact.max' => 'شماره تماس اضطراری نمی‌تواند بیشتر از 20 کاراکتر باشد.',
            
            'contacts.*.address.string' => 'آدرس باید به صورت متنی باشد.',
            'contacts.*.address.max' => 'آدرس نمی‌تواند بیشتر از 500 کاراکتر باشد.',
            
            'contacts.*.notes.string' => 'یادداشت‌ها باید به صورت متنی باشد.',
            'contacts.*.notes.max' => 'یادداشت‌ها نمی‌تواند بیشتر از 1000 کاراکتر باشد.',
            
            // Foreign key validation messages
            'contacts.*.how_introduced_id.integer' => 'نحوه آشنایی انتخاب شده نامعتبر است.',
            'contacts.*.how_introduced_id.exists' => 'نحوه آشنایی انتخاب شده در این سالن تعریف نشده است.',
            
            'contacts.*.profession_id.integer' => 'شغل انتخاب شده نامعتبر است.',
            'contacts.*.profession_id.exists' => 'شغل انتخاب شده در این سالن تعریف نشده است.',
            
            'contacts.*.age_range_id.integer' => 'بازه سنی انتخاب شده نامعتبر است.',
            'contacts.*.age_range_id.exists' => 'بازه سنی انتخاب شده در این سالن تعریف نشده است.',
            
            'contacts.*.customer_group_id.integer' => 'گروه مشتری انتخاب شده نامعتبر است.',
            'contacts.*.customer_group_id.exists' => 'گروه مشتری انتخاب شده در این سالن تعریف نشده است.',
            
            'contacts.*.city_id.integer' => 'شهر انتخاب شده نامعتبر است.',
            'contacts.*.city_id.exists' => 'شهر انتخاب شده در سیستم موجود نیست.',
            
            'update_existing.boolean' => 'فیلد به‌روزرسانی موجودی باید بولین باشد.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean phone numbers and remove any non-numeric characters except +
        if ($this->has('contacts')) {
            $contacts = $this->input('contacts');
            foreach ($contacts as $index => $contact) {
                // Clean phone number: remove spaces, dashes, parentheses, but keep +
                if (isset($contact['phone_number'])) {
                    $cleanPhone = preg_replace('/[^\d+]/', '', $contact['phone_number']);
                    $contacts[$index]['phone_number'] = $cleanPhone;
                }
                
                // Clean emergency contact
                if (isset($contact['emergency_contact'])) {
                    $cleanEmergency = preg_replace('/[^\d+]/', '', $contact['emergency_contact']);
                    $contacts[$index]['emergency_contact'] = $cleanEmergency;
                }
                
                // Convert Persian/Arabic numbers to English in birth_date
                if (isset($contact['birth_date']) && $contact['birth_date'] !== null) {
                    $contacts[$index]['birth_date'] = $this->convertToEnglishNumbers($contact['birth_date']);
                }
            }
            $this->merge(['contacts' => $contacts]);
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

        // Convert Jalali dates to Gregorian for database storage
        if (isset($validatedData['contacts'])) {
            foreach ($validatedData['contacts'] as $index => $contact) {
                if (isset($contact['birth_date']) && $contact['birth_date'] !== null) {
                    try {
                        $jalaliDate = Jalalian::fromFormat('Y/m/d', $contact['birth_date']);
                        $validatedData['contacts'][$index]['birth_date'] = $jalaliDate->toCarbon()->toDateString();
                    } catch (\Exception $e) {
                        $validatedData['contacts'][$index]['birth_date'] = null;
                    }
                }
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
