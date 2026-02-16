<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * اضافه کردن فیلدهای ماه و سال برای پرداخت‌های پورسانت
     */
    public function up(): void
    {
        Schema::table('staff_commission_transactions', function (Blueprint $table) {
            $table->integer('for_month')->nullable()->after('paid_at')->comment('ماه مربوط به پرداخت (برای پرداخت‌ها)');
            $table->integer('for_year')->nullable()->after('for_month')->comment('سال مربوط به پرداخت (برای پرداخت‌ها)');
            
            // اضافه کردن ایندکس برای جستجوی سریع‌تر
            $table->index(['staff_id', 'for_year', 'for_month'], 'sct_staff_year_month_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_commission_transactions', function (Blueprint $table) {
            $table->dropIndex('sct_staff_year_month_idx');
            $table->dropColumn(['for_month', 'for_year']);
        });
    }
};
