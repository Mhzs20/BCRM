<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalonSmsTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'salon_id',
        'category_id',
        'event_type',
        'title',
        'template',
        'is_active',
        'template_type',
        'estimated_parts',
        'estimated_cost',
        'variables',
    ];

    protected $casts = [
        'variables' => 'array',
    ];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    public function category()
    {
        return $this->belongsTo(SmsTemplateCategory::class, 'category_id');
    }

    /**
     * محاسبه تعداد پارت‌های پیامک با جایگزینی متغیرها
     * 
     * @param string|null $customerName نام مشتری برای جایگزینی (پیش‌فرض: نام نمونه 10 کاراکتری)
     * @param string|null $salonName نام سالن برای جایگزینی (پیش‌فرض: نام نمونه 15 کاراکتری)
     * @param array $extraVariables متغیرهای اضافی برای جایگزینی
     * @return int
     */
    public function calculateEstimatedParts(?string $customerName = null, ?string $salonName = null, array $extraVariables = []): int
    {
        // مقادیر پیش‌فرض برای متغیرها
        $defaultCustomerName = $customerName ?? 'نام مشتری نمونه'; // 15 کاراکتر
        $defaultSalonName = $salonName ?? 'سالن زیبایی نمونه'; // 17 کاراکتر
        
        // جایگزینی متغیرها در تمپلیت
        $compiledTemplate = $this->template;
        
        // جایگزینی customer_name
        $compiledTemplate = preg_replace(
            ['/\{\{\s*customer_name\s*\}\}/u', '/\{\s*customer_name\s*\}/u'],
            $defaultCustomerName,
            $compiledTemplate
        );
        
        // جایگزینی salon_name
        $compiledTemplate = preg_replace(
            ['/\{\{\s*salon_name\s*\}\}/u', '/\{\s*salon_name\s*\}/u'],
            $defaultSalonName,
            $compiledTemplate
        );
        
        // جایگزینی متغیرهای اضافی
        foreach ($extraVariables as $key => $value) {
            $escapedKey = preg_quote($key, '/');
            $compiledTemplate = preg_replace(
                ["/\{\{\s*{$escapedKey}\s*\}\}/u", "/\{\s*{$escapedKey}\s*\}/u"],
                $value,
                $compiledTemplate
            );
        }
        
        // محاسبه تعداد کاراکترها
        $characterCount = mb_strlen($compiledTemplate);
        
        // تشخیص نوع زبان (فارسی یا انگلیسی)
        $isPersian = preg_match('/[\x{0600}-\x{06FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $compiledTemplate);
        
        // محدودیت هر پارت از تنظیمات
        $limitPerPart = $isPersian ? 
            (int)(Setting::where('key', 'sms_part_char_limit_fa')->first()->value ?? 70) :
            (int)(Setting::where('key', 'sms_part_char_limit_en')->first()->value ?? 160);
        
        if ($characterCount === 0) {
            return 0;
        }
        
        return (int)ceil($characterCount / $limitPerPart);
    }

    /**
     * محاسبه هزینه تخمینی پیامک بر اساس تعداد پارت‌ها
     * 
     * @param string|null $customerName نام مشتری برای جایگزینی
     * @param string|null $salonName نام سالن برای جایگزینی
     * @param array $extraVariables متغیرهای اضافی برای جایگزینی
     * @return float
     */
    public function calculateEstimatedCost(?string $customerName = null, ?string $salonName = null, array $extraVariables = []): float
    {
        $parts = $this->calculateEstimatedParts($customerName, $salonName, $extraVariables);
        
        // هزینه هر پارت از تنظیمات
        $costPerPart = (float)(Setting::where('key', 'sms_cost_per_part')->first()->value ?? 100);
        
        return $parts * $costPerPart;
    }

    /**
     * به‌روزرسانی خودکار فیلدهای estimated_parts و estimated_cost
     * 
     * @param string|null $customerName نام مشتری برای جایگزینی
     * @param string|null $salonName نام سالن برای جایگزینی
     * @param array $extraVariables متغیرهای اضافی برای جایگزینی
     * @return void
     */
    public function updateEstimatedValues(?string $customerName = null, ?string $salonName = null, array $extraVariables = []): void
    {
        $this->estimated_parts = $this->calculateEstimatedParts($customerName, $salonName, $extraVariables);
        $this->estimated_cost = (int)$this->calculateEstimatedCost($customerName, $salonName, $extraVariables);
        $this->save();
    }
}
