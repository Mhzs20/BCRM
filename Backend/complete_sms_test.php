<?php
// تست کامل کد SmsService در سرور
// دستور اجرا: php complete_sms_test.php

require_once __DIR__ . '/vendor/autoload.php';

try {
    // Load environment variables
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Configure Laravel app
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    echo "=== تست کامل SmsService در سرور ===\n\n";
    
    // 1. بررسی وجود متد helper
    $smsService = new \App\Services\SmsService();
    $reflection = new ReflectionClass($smsService);
    
    if ($reflection->hasMethod('getAppointmentTemplateData')) {
        echo "✅ متد getAppointmentTemplateData موجود است.\n";
    } else {
        echo "❌ متد getAppointmentTemplateData موجود نیست!\n";
        exit;
    }
    
    if ($reflection->hasMethod('getAppointmentDateTime')) {
        echo "✅ متد getAppointmentDateTime موجود است.\n";
    } else {
        echo "❌ متد getAppointmentDateTime موجود نیست!\n";
        exit;
    }
    
    // 2. بررسی قالب‌های سیستمی
    echo "\n--- بررسی قالب‌های سیستمی ---\n";
    $systemTemplates = \App\Models\SalonSmsTemplate::whereNull('salon_id')
        ->where('event_type', 'appointment_confirmation')
        ->where('template_type', 'system_event')
        ->get();
        
    if ($systemTemplates->count() > 0) {
        echo "⚠️ {$systemTemplates->count()} قالب سیستمی پیدا شد:\n";
        foreach ($systemTemplates as $template) {
            echo "  - {$template->template}\n";
        }
        echo "این قالب‌ها ممکن است مشکل ایجاد کنند.\n";
    } else {
        echo "✅ هیچ قالب سیستمی مشکل‌ساز پیدا نشد.\n";
    }
    
    // 3. تست با یک نوبت واقعی
    echo "\n--- تست با نوبت واقعی ---\n";
    $appointment = \App\Models\Appointment::with(['customer', 'salon'])->latest()->first();
    
    if (!$appointment || !$appointment->customer || !$appointment->salon) {
        echo "❌ نوبت مناسب برای تست پیدا نشد.\n";
        exit;
    }
    
    echo "نوبت: {$appointment->id}\n";
    echo "تاریخ خام: {$appointment->getAttributes()['appointment_date']}\n";
    echo "زمان خام: {$appointment->getAttributes()['start_time']}\n";
    
    // تست متد helper
    $dateTimeMethod = $reflection->getMethod('getAppointmentDateTime');
    $dateTimeMethod->setAccessible(true);
    $appointmentDateTime = $dateTimeMethod->invoke($smsService, $appointment);
    
    $templateDataMethod = $reflection->getMethod('getAppointmentTemplateData');
    $templateDataMethod->setAccessible(true);
    $templateData = $templateDataMethod->invoke($smsService, $appointment);
    
    echo "تاریخ محاسبه شده: {$templateData['appointment_date']}\n";
    echo "زمان محاسبه شده: {$templateData['appointment_time']}\n";
    
    $todayJalali = \Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::today())->format('Y/m/d');
    echo "تاریخ امروز: {$todayJalali}\n";
    
    if ($templateData['appointment_date'] === $todayJalali) {
        echo "❌ مشکل: تاریخ محاسبه شده = تاریخ امروز (اشتباه است!)\n";
    } else {
        echo "✅ تاریخ محاسبه شده درست است.\n";
    }
    
    // 4. تست کامل SmsService
    echo "\n--- تست کامل sendAppointmentConfirmation ---\n";
    
    $testData = [
        'customer_name' => $appointment->customer->name,
        'salon_name' => $appointment->salon->name,
        'details_url' => url('a/' . $appointment->hash),
    ];
    
    $finalData = array_merge($testData, $templateData);
    
    echo "داده‌های نهایی:\n";
    foreach ($finalData as $key => $value) {
        echo "  {$key}: {$value}\n";
    }
    
    // گرفتن قالب
    $smsTemplate = $appointment->salon->getSmsTemplate('appointment_confirmation');
    
    if ($smsTemplate && $smsTemplate->is_active) {
        echo "\nقالب اختصاصی: {$smsTemplate->template}\n";
        $templateText = $smsTemplate->template;
    } else {
        $defaultMethod = $reflection->getMethod('getDefaultTextForEventType');
        $defaultMethod->setAccessible(true);
        $templateText = $defaultMethod->invoke($smsService, 'appointment_confirmation');
        echo "\nقالب پیش‌فرض: {$templateText}\n";
    }
    
    // کامپایل پیام
    $compileMethod = $reflection->getMethod('compileTemplate');
    $compileMethod->setAccessible(true);
    $finalMessage = $compileMethod->invoke($smsService, $templateText, $finalData);
    
    echo "\n=== پیام نهایی ===\n";
    echo $finalMessage . "\n";
    
    // بررسی نهایی
    if (str_contains($finalMessage, $templateData['appointment_date'])) {
        echo "\n✅ SUCCESS: تاریخ درست در پیام موجود است!\n";
    } elseif (str_contains($finalMessage, $todayJalali)) {
        echo "\n❌ ERROR: تاریخ امروز (اشتباه) در پیام موجود است!\n";
    } else {
        echo "\n⚠️ WARNING: هیچ تاریخی در پیام پیدا نشد.\n";
    }
    
    // 5. پیشنهادات
    echo "\n=== پیشنهادات ===\n";
    if ($systemTemplates->count() > 0) {
        echo "1. قالب‌های سیستمی را حذف کنید:\n";
        echo "   php artisan tinker\n";
        echo "   >>> \\App\\Models\\SalonSmsTemplate::whereNull('salon_id')->where('event_type', 'appointment_confirmation')->delete();\n";
        echo "   >>> exit\n\n";
    }
    
    echo "2. Cache را پاک کنید:\n";
    echo "   php artisan cache:clear\n";
    echo "   php artisan config:clear\n\n";
    
    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "3. ✅ OPcache پاک شد.\n";
    } else {
        echo "3. OPcache را دستی پاک کنید.\n";
    }
    
    echo "\n=== تست کامل شد ===\n";
    
} catch (Exception $e) {
    echo "❌ خطا: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}