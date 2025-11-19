<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SalonSmsTemplate;
use App\Models\SmsTemplateCategory;

echo "=== وضعیت تمپلیت‌های ترمیم و تولد ===\n\n";

// تمپلیت‌های ترمیم
$renewalCategory = SmsTemplateCategory::where('name', 'LIKE', '%ترمیم%')->first();
if ($renewalCategory) {
    echo "📋 تمپلیت‌های ترمیم (دسته: {$renewalCategory->name}):\n";
    $renewalTemplates = SalonSmsTemplate::where('category_id', $renewalCategory->id)->get();
    
    foreach ($renewalTemplates as $template) {
        $status = ($template->estimated_parts !== null && $template->estimated_cost !== null) ? '✅' : '❌';
        echo "{$status} ID: {$template->id} - {$template->title}\n";
        echo "   پارت: " . ($template->estimated_parts ?? 'NULL') . " | هزینه: " . ($template->estimated_cost ?? 'NULL') . "\n";
    }
} else {
    echo "⚠️ دسته ترمیم یافت نشد\n";
}

echo "\n";

// تمپلیت‌های تولد
$birthdayCategory = SmsTemplateCategory::where('name', 'LIKE', '%تولد%')->first();
if ($birthdayCategory) {
    echo "🎂 تمپلیت‌های تولد (دسته: {$birthdayCategory->name}):\n";
    $birthdayTemplates = SalonSmsTemplate::where('category_id', $birthdayCategory->id)->get();
    
    foreach ($birthdayTemplates as $template) {
        $status = ($template->estimated_parts !== null && $template->estimated_cost !== null) ? '✅' : '❌';
        echo "{$status} ID: {$template->id} - {$template->title}\n";
        echo "   پارت: " . ($template->estimated_parts ?? 'NULL') . " | هزینه: " . ($template->estimated_cost ?? 'NULL') . "\n";
    }
} else {
    echo "⚠️ دسته تولد یافت نشد\n";
}

echo "\n📊 خلاصه آمار:\n";
$totalTemplates = SalonSmsTemplate::count();
$fixedTemplates = SalonSmsTemplate::whereNotNull('estimated_parts')->whereNotNull('estimated_cost')->count();
$nullTemplates = SalonSmsTemplate::whereNull('estimated_parts')->orWhereNull('estimated_cost')->count();

echo "- کل تمپلیت‌ها: {$totalTemplates}\n";
echo "- فیکس شده: {$fixedTemplates}\n";
echo "- نیاز به فیکس: {$nullTemplates}\n";
