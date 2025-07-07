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
            $table->integer('reminder_time')->nullable()->comment('Reminder time in hours before the appointment');
            $table->boolean('send_reminder_sms')->default(true);
            $table->enum('reminder_sms_status', ['not_sent', 'pending', 'sent'])->default('not_sent');
            $table->boolean('send_satisfaction_sms')->default(true);
            $table->enum('satisfaction_sms_status', ['not_sent', 'pending', 'sent'])->default('not_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'reminder_time',
                'send_reminder_sms',
                'reminder_sms_status',
                'send_satisfaction_sms',
                'satisfaction_sms_status'
            ]);
        });
    }
};
