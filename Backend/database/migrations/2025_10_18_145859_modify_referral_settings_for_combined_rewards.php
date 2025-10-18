<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('referral_settings', function (Blueprint $table) {
            // حذف ستون reward_type قدیمی که انتخاب exclusive بود
            if (Schema::hasColumn('referral_settings', 'reward_type')) {
                $table->dropColumn('reward_type');
            }
            
            // اضافه کردن ستون‌های جدید برای کنترل مجزا هر نوع پاداش
            if (!Schema::hasColumn('referral_settings', 'enable_signup_reward')) {
                $table->boolean('enable_signup_reward')->default(true)->comment('فعال بودن پاداش ثبت نام');
            }
            
            if (!Schema::hasColumn('referral_settings', 'enable_purchase_reward')) {
                $table->boolean('enable_purchase_reward')->default(false)->comment('فعال بودن پاداش خرید');
            }
            
            // حذف متن پیام دعوت از تنظیمات (منتقل به frontend)
            if (Schema::hasColumn('referral_settings', 'referral_message')) {
                $table->dropColumn('referral_message');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referral_settings', function (Blueprint $table) {
            // بازگرداندن ستون reward_type
            if (!Schema::hasColumn('referral_settings', 'reward_type')) {
                $table->enum('reward_type', ['fixed_amount', 'purchase_percentage'])->default('fixed_amount');
            }
            
            // حذف ستون‌های جدید
            if (Schema::hasColumn('referral_settings', 'enable_signup_reward')) {
                $table->dropColumn('enable_signup_reward');
            }
            
            if (Schema::hasColumn('referral_settings', 'enable_purchase_reward')) {
                $table->dropColumn('enable_purchase_reward');
            }
            
            // بازگرداندن متن پیام
            if (!Schema::hasColumn('referral_settings', 'referral_message')) {
                $table->text('referral_message')->nullable();
            }
        });
    }
};
