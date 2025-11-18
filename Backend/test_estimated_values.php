<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SalonSmsTemplate;
use App\Models\Setting;

echo "=== تست محاسبه estimated_parts و estimated_cost ===\n\n";

// بررسی تنظیمات
echo "📊 تنظیمات فعلی:\n";
$smsCostPerPart = Setting::where('key', 'sms_cost_per_part')->first()->value ?? 100;
$smsPartCharLimitFa = Setting::where('key', 'sms_part_char_limit_fa')->first()->value ?? 70;
$smsPartCharLimitEn = Setting::where('key', 'sms_part_char_limit_en')->first()->value ?? 160;

echo "- هزینه هر پارت: {$smsCostPerPart} واحد\n";
echo "- محدودیت کاراکتر فارسی: {$smsPartCharLimitFa}\n";
echo "- محدودیت کاراکتر انگلیسی: {$smsPartCharLimitEn}\n\n";

// تست با یک تمپلیت نمونه
echo "🧪 تست با تمپلیت نمونه:\n";
$sampleTemplate = new SalonSmsTemplate([
    'template' => 'مشتری گرامی {{customer_name}}، تولدتان مبارک! آرزوی سلامتی و شادی برای شما در {{salon_name}}.'
]);

echo "تمپلیت: {$sampleTemplate->template}\n\n";

// محاسبه با نام‌های پیش‌فرض
$parts = $sampleTemplate->calculateEstimatedParts();
$cost = $sampleTemplate->calculateEstimatedCost();

echo "نتیجه با نام‌های پیش‌فرض:\n";
echo "- تعداد پارت‌ها: {$parts}\n";
echo "- هزینه تخمینی: {$cost} واحد\n\n";

// محاسبه با نام‌های واقعی
$parts2 = $sampleTemplate->calculateEstimatedParts('علی احمدی', 'سالن زیبایی پارسا');
$cost2 = $sampleTemplate->calculateEstimatedCost('علی احمدی', 'سالن زیبایی پارسا');

echo "نتیجه با نام‌های واقعی:\n";
echo "- تعداد پارت‌ها: {$parts2}\n";
echo "- هزینه تخمینی: {$cost2} واحد\n\n";

// تست با تمپلیت‌های موجود در دیتابیس
echo "📋 بررسی 5 تمپلیت اول از دیتابیس:\n";
$templates = SalonSmsTemplate::whereNull('salon_id')->take(5)->get();

foreach ($templates as $template) {
    echo "\n---\n";
    echo "ID: {$template->id}\n";
    echo "عنوان: " . ($template->title ?? $template->event_type ?? 'N/A') . "\n";
    echo "تمپلیت: " . mb_substr($template->template, 0, 50) . "...\n";
    echo "estimated_parts فعلی در DB: " . ($template->estimated_parts ?? 'NULL') . "\n";
    echo "estimated_cost فعلی در DB: " . ($template->estimated_cost ?? 'NULL') . "\n";
    
    $calculatedParts = $template->calculateEstimatedParts();
    $calculatedCost = $template->calculateEstimatedCost();
    
    echo "محاسبه جدید - پارت‌ها: {$calculatedParts}\n";
    echo "محاسبه جدید - هزینه: {$calculatedCost}\n";
}

echo "\n\n✅ تست با موفقیت انجام شد!\n";
