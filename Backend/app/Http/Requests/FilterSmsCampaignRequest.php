<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterSmsCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $salon = $this->route('salon');
        $salonId = $salon instanceof \App\Models\Salon ? $salon->id : $salon;
        
        return [
            'min_age' => 'nullable|integer|min:0',
            'max_age' => 'nullable|integer|min:0|gte:min_age',
            'profession_id' => 'nullable|array',
            'profession_id.*' => "integer|exists:professions,id,salon_id,{$salonId}",
            'customer_group_id' => 'nullable|array',
            'customer_group_id.*' => "integer|exists:customer_groups,id,salon_id,{$salonId}",
            'min_payment' => 'nullable|numeric|min:0',
            'max_payment' => 'nullable|numeric|min:0|gte:min_payment',
            'how_introduced_id' => 'nullable|array',
            'how_introduced_id.*' => "integer|exists:how_introduceds,id,salon_id,{$salonId}",
            'min_appointments' => 'nullable|integer|min:0',
            'max_appointments' => 'nullable|integer|min:0|gte:min_appointments',
            'sms_template_id' => 'nullable|integer|exists:salon_sms_templates,id',
            'message' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'profession_id.*.exists' => 'شغل انتخاب شده برای این سالن وجود ندارد.',
            'customer_group_id.*.exists' => 'گروه مشتری انتخاب شده برای این سالن وجود ندارد.',
            'how_introduced_id.*.exists' => 'نحوه آشنایی انتخاب شده برای این سالن وجود ندارد.',
            'max_age.gte' => 'حداکثر سن باید بزرگتر یا مساوی حداقل سن باشد.',
            'max_payment.gte' => 'حداکثر مبلغ پرداخت باید بزرگتر یا مساوی حداقل مبلغ باشد.',
            'max_appointments.gte' => 'حداکثر تعداد نوبت باید بزرگتر یا مساوی حداقل تعداد باشد.',
        ];
    }
}
