<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppointmentsTable extends Migration
{
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('salon_staff')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->date('date');
            $table->time('time');
            $table->string('status')->nullable();
            $table->text('prerequisites')->nullable();
            $table->boolean('deposit_required')->default(false);
            $table->boolean('deposit_paid')->default(false);
            $table->timestamp('reminder_sms_sent_at')->nullable();
            $table->timestamp('survey_sms_sent_at')->nullable();
            $table->foreignId('feedback_id')->nullable()->constrained('customer_feedback')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointments');
    }
}
