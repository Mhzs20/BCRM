<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configure Laravel app
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== تست مستقیم SmsService برای appointment_confirmation ===\n\n";

// پیدا کردن یک نوبت نمونه
$appointment = \App\Models\Appointment::with(['customer', 'salon'])->latest()->first();

if (!$appointment || !$appointment->customer || !$appointment->salon) {
    echo "❌ نوبت مناسب برای تست پیدا نشد.\n";
    exit;
}

echo "نوبت ID: " . $appointment->id . "\n";
echo "تاریخ نوبت (raw): " . $appointment->getAttributes()['appointment_date'] . "\n";
echo "زمان نوبت (raw): " . $appointment->getAttributes()['start_time'] . "\n";
echo "مشتری: " . $appointment->customer->name . "\n";
echo "سالن: " . $appointment->salon->name . "\n\n";

// تست مستقیم SmsService
$smsService = new \App\Services\SmsService();

try {
    echo "=== تست ارسال پیام تأیید نوبت ===\n";
    
    // فراخوانی مستقیم متد sendAppointmentConfirmation (بدون ارسال واقعی)
    $reflection = new ReflectionClass($smsService);
    
    // شبیه‌سازی داده‌های template مانند کد اصلی
    $appointmentDateTime = \Carbon\Carbon::parse($appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->start_time, 'Asia/Tehran');
    $detailsUrl = url('a/' . $appointment->hash);
    
    $dataForTemplate = [
        'customer_name' => $appointment->customer->name,
        'salon_name' => $appointment->salon->name,
        'appointment_date' => \Morilog\Jalali\Jalalian::fromCarbon($appointmentDateTime)->format('Y/m/d'),
        'appointment_time' => $appointmentDateTime->format('H:i'),
        'staff_name' => $appointment->staff ? $appointment->staff->full_name : 'پرسنل محترم',
        'services_list' => $appointment->services->pluck('name')->implode('، '),
        'appointment_cost' => number_format($appointment->total_price ?: 0) . ' تومان',
        'details_url' => $detailsUrl,
    ];
    
    echo "داده‌های محاسبه شده:\n";
    foreach ($dataForTemplate as $key => $value) {
        echo "  {$key}: {$value}\n";
    }
    
    // گرفتن قالب مانند کد اصلی
    $smsTemplate = $appointment->salon->getSmsTemplate('appointment_confirmation');
    
    $templateText = null;
    if ($smsTemplate && $smsTemplate->is_active) {
        $templateText = $smsTemplate->template;
        echo "\nقالب اختصاصی پیدا شد: " . $templateText . "\n";
    } else {
        // استفاده از متد private getDefaultTextForEventType
        $method = $reflection->getMethod('getDefaultTextForEventType');
        $method->setAccessible(true);
        $templateText = $method->invoke($smsService, 'appointment_confirmation', $dataForTemplate);
        echo "\nقالب پیش‌فرض: " . $templateText . "\n";
    }
    
    // کامپایل template
    $compileMethod = $reflection->getMethod('compileTemplate');
    $compileMethod->setAccessible(true);
    $message = $compileMethod->invoke($smsService, $templateText, $dataForTemplate);
    
    echo "\n=== پیام نهایی ===\n";
    echo $message . "\n";
    
    // بررسی که آیا تاریخ درست است
    $expectedDate = \Morilog\Jalali\Jalalian::fromCarbon($appointmentDateTime)->format('Y/m/d');
    $todayDate = \Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::today())->format('Y/m/d');
    
    echo "\n=== بررسی تاریخ ===\n";
    echo "تاریخ مورد انتظار: {$expectedDate}\n";
    echo "تاریخ امروز: {$todayDate}\n";
    
    if (str_contains($message, $expectedDate)) {
        echo "✅ تاریخ درست در پیام موجود است.\n";
    } elseif (str_contains($message, $todayDate)) {
        echo "❌ تاریخ امروز (اشتباه) در پیام موجود است!\n";
    } else {
        echo "⚠️ هیچ کدام از تاریخ‌ها در پیام پیدا نشد.\n";
    }
    
} catch (Exception $e) {
    echo "❌ خطا: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}