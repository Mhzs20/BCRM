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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('salon_staff')->onDelete('cascade');
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('total_price', 10, 2)->nullable();
            $table->integer('total_duration')->nullable();
            $table->string('status')->default('confirmed'); // e.g., confirmed, completed, cancelled
            $table->text('notes')->nullable();
            $table->boolean('deposit_required')->default(false);
            $table->boolean('deposit_paid')->default(false);
            $table->timestamp('reminder_sms_sent_at')->nullable();
            $table->timestamp('survey_sms_sent_at')->nullable();
            $table->foreignId('feedback_id')->nullable()->constrained('customer_feedback')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
