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
        Schema::create('pending_appointment_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('salon_staff')->onDelete('cascade');
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('total_price', 10, 2)->default(0);
            $table->integer('total_duration')->default(0);
            $table->string('status')->default('pending_confirmation');
            $table->text('notes')->nullable();
            $table->text('internal_note')->nullable();
            $table->boolean('deposit_required')->default(false);
            $table->boolean('deposit_paid')->default(false);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->string('deposit_payment_method')->nullable();
            $table->integer('reminder_time')->nullable();
            $table->boolean('send_reminder_sms')->default(true);
            $table->boolean('send_satisfaction_sms')->default(true);
            $table->boolean('send_confirmation_sms')->default(true);
            $table->unsignedBigInteger('confirmation_sms_template_id')->nullable();
            $table->unsignedBigInteger('reminder_sms_template_id')->nullable();
            $table->json('service_ids')->nullable();
            $table->json('conflicting_appointments')->nullable();
            $table->json('old_data')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['salon_id', 'appointment_id']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_appointment_updates');
    }
};
