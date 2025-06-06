<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::create('salon_sms_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->string('event_type')->comment('مانند: appointment_confirmation, appointment_reminder, birthday_greeting, service_specific_notes');
            $table->text('template')->nullable()->comment('متن قالب پیامک با placeholder ها. مثال: مشتری گرامی {customer_name}...');
            $table->boolean('is_active')->default(true)->comment('آیا ارسال این نوع پیامک برای سالن فعال است؟');
            $table->unique(['salon_id', 'event_type']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_sms_templates');
    }
};
