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
        Schema::table('appointments', function (Blueprint $table) {
            // اضافه کردن فیلدهای انتخاب تمپلیت برای پیامک ثبت نوبت
            $table->boolean('send_confirmation_sms')->default(true)->after('send_satisfaction_sms');
            $table->unsignedBigInteger('confirmation_sms_template_id')->nullable()->after('send_confirmation_sms');
            
            // اضافه کردن فیلد انتخاب تمپلیت برای پیامک یادآوری
            $table->unsignedBigInteger('reminder_sms_template_id')->nullable()->after('confirmation_sms_template_id');
            
            // اضافه کردن کلیدهای خارجی
            $table->foreign('confirmation_sms_template_id')->references('id')->on('salon_sms_templates')->onDelete('set null');
            $table->foreign('reminder_sms_template_id')->references('id')->on('salon_sms_templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['confirmation_sms_template_id']);
            $table->dropForeign(['reminder_sms_template_id']);
            $table->dropColumn([
                'send_confirmation_sms',
                'confirmation_sms_template_id',
                'reminder_sms_template_id'
            ]);
        });
    }
};
