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
    {+
        Schema::create('pending_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('salon_staff')->onDelete('cascade');
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('total_price', 10, 2)->nullable();
            $table->integer('total_duration');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->text('internal_note')->nullable();
            $table->boolean('deposit_required')->default(false);
            $table->boolean('deposit_paid')->default(false);
            $table->decimal('deposit_amount', 10, 2)->default(0.00);
            $table->string('deposit_payment_method')->nullable();
            $table->integer('reminder_time')->nullable();
            $table->boolean('send_reminder_sms')->default(true);
            $table->boolean('send_satisfaction_sms')->default(true);
            $table->boolean('send_confirmation_sms')->default(true);
            $table->unsignedBigInteger('confirmation_sms_template_id')->nullable();
            $table->unsignedBigInteger('reminder_sms_template_id')->nullable();
            $table->json('service_ids'); // Store selected service IDs as JSON
            $table->json('new_customer_data')->nullable(); // Store new customer data if applicable
            $table->json('conflicting_appointments')->nullable(); // Store any conflicting appointments
            $table->timestamp('expires_at'); // Expiration time for the pending appointment
            $table->timestamps();
            
            $table->index(['salon_id', 'expires_at']);
            $table->index(['staff_id', 'appointment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_appointments');
    }
};
