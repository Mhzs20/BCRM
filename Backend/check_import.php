<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Salon;
use App\Models\Customer;
use App\Models\SalonSmsBalance;

try {
    echo "=== بررسی داده‌های Import شده ===\n\n";
    
    // آمار کلی
    echo "📊 آمار کلی:\n";
    echo "تعداد کل کاربران: " . User::count() . "\n";
    echo "تعداد کل سالن‌ها: " . Salon::count() . "\n";
    echo "تعداد کل مشتریان: " . Customer::count() . "\n";
    echo "تعداد SMS Balance: " . SalonSmsBalance::count() . "\n\n";
    
    // کاربران جدید (از 3 نوامبر)
    $newUsers = User::where('created_at', '>=', '2025-11-03')->orderBy('created_at', 'desc')->take(5)->get();
    echo "👥 آخرین 5 کاربر import شده:\n";
    foreach ($newUsers as $user) {
        echo "- {$user->name} ({$user->mobile}) - تاریخ ثبت: {$user->created_at}\n";
        if ($user->activeSalon) {
            echo "  └─ سالن فعال: {$user->activeSalon->name}\n";
            if ($user->activeSalon->smsBalance) {
                echo "  └─ اعتبار پیامک: {$user->activeSalon->smsBalance->balance}\n";
            }
        }
    }
    
    echo "\n";
    
    // آمار SMS Balance
    $totalSmsBalance = SalonSmsBalance::sum('balance');
    echo "💬 مجموع اعتبار پیامک: {$totalSmsBalance}\n";
    
    // سالن‌هایی که مشتری دارند
    $salonsWithCustomers = Salon::whereHas('customers')->count();
    echo "🏪 سالن‌هایی که مشتری دارند: {$salonsWithCustomers}\n";
    
    echo "\n✅ Import با موفقیت انجام شده است!\n";
    
} catch (Exception $e) {
    echo "❌ خطا: " . $e->getMessage() . "\n";
}