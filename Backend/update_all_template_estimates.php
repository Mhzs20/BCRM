<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SalonSmsTemplate;

echo "=== به‌روزرسانی estimated_parts و estimated_cost برای تمام تمپلیت‌ها ===\n\n";

$templates = SalonSmsTemplate::all();
$totalCount = $templates->count();
$updatedCount = 0;

echo "📊 تعداد کل تمپلیت‌ها: {$totalCount}\n\n";

foreach ($templates as $template) {
    echo "🔄 در حال پردازش ID: {$template->id}";
    if ($template->title) {
        echo " - {$template->title}";
    } elseif ($template->event_type) {
        echo " - {$template->event_type}";
    }
    echo "\n";
    
    // محاسبه و به‌روزرسانی
    try {
        $template->updateEstimatedValues();
        $updatedCount++;
        echo "   ✅ به‌روز شد - پارت‌ها: {$template->estimated_parts}, هزینه: {$template->estimated_cost}\n";
    } catch (\Exception $e) {
        echo "   ❌ خطا: " . $e->getMessage() . "\n";
    }
}

echo "\n📈 گزارش نهایی:\n";
echo "- تعداد کل: {$totalCount}\n";
echo "- به‌روز شده: {$updatedCount}\n";
echo "- ناموفق: " . ($totalCount - $updatedCount) . "\n";

echo "\n✅ فرآیند به‌روزرسانی کامل شد!\n";
