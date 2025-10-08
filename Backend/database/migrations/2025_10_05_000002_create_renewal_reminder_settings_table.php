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
        Schema::create('renewal_reminder_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->boolean('is_active')->default(false)->comment('آیا یادآوری ترمیم برای این سالن فعال است؟');
            $table->foreignId('active_template_id')->nullable()->constrained('salon_sms_templates')->onDelete('set null')->comment('قالب فعال انتخاب شده برای یادآوری ترمیم');
            $table->integer('reminder_days_before')->default(7)->comment('چند روز قبل از موعد ترمیم یادآوری ارسال شود');
            $table->time('reminder_time')->default('10:00')->comment('ساعت ارسال یادآوری');
            $table->timestamps();
            
            $table->unique('salon_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('renewal_reminder_settings');
    }
};