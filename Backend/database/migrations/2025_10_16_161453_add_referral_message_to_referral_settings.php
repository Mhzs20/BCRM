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
            $table->text('referral_message')->nullable()->after('send_sms_notifications')
                  ->comment('پیام دعوت که همراه با کد رفرال ارسال می‌شود');
            $table->text('welcome_bonus_message')->nullable()->after('referral_message')
                  ->comment('پیام خوش‌آمدگویی برای کاربران جدید');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referral_settings', function (Blueprint $table) {
            $table->dropColumn(['referral_message', 'welcome_bonus_message']);
        });
    }
};
