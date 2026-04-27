<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * اضافه کردن تنظیمات پورسانت به جدول کارکنان
     */
    public function up(): void
    {
        Schema::table('salon_staff', function (Blueprint $table) {
            // سقف پورسانت ماهانه (null = بدون محدودیت)
            $table->decimal('monthly_commission_cap', 12, 2)->nullable()->after('total_commission_paid')
                ->comment('سقف پورسانت ماهانه - null یعنی بدون محدودیت');
            
            // آیا تخفیف مشتری روی پورسانت تاثیر بگذارد؟
            $table->boolean('apply_discount_to_commission')->default(false)->after('monthly_commission_cap')
                ->comment('اگر true باشد، پورسانت از مبلغ بعد از تخفیف محاسبه می‌شود');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salon_staff', function (Blueprint $table) {
            $table->dropColumn(['monthly_commission_cap', 'apply_discount_to_commission']);
        });
    }
};
