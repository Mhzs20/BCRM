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
        Schema::create('renewal_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('salon_sms_templates')->onDelete('set null');
            $table->text('message_content')->comment('متن پیام ارسال شده');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->string('sms_message_id')->nullable()->comment('شناسه پیام از سرویس SMS');
            $table->text('error_message')->nullable()->comment('پیام خطا در صورت عدم ارسال');
            $table->timestamp('sent_at')->nullable()->comment('زمان ارسال');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('renewal_reminder_logs');
    }
};