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
        Schema::create('service_renewal_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->boolean('is_active')->default(true)->comment('آیا یادآوری ترمیم برای این سرویس فعال است؟');
            $table->integer('renewal_period_days')->default(30)->comment('تعداد روز بعد از انجام سرویس که ترمیم نیاز است');
            $table->integer('reminder_days_before')->default(7)->comment('چند روز قبل از موعد ترمیم یادآوری ارسال شود');
            $table->time('reminder_time')->default('10:00')->comment('ساعت ارسال یادآوری');
            $table->foreignId('template_id')->nullable()->constrained('salon_sms_templates')->onDelete('set null')->comment('قالب انتخاب شده برای این سرویس');
            $table->timestamps();
            
            $table->unique(['salon_id', 'service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_renewal_settings');
    }
};