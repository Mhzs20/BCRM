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
            $table->string('reminder_sms_message_id')->nullable()->after('reminder_sms_status');
            $table->string('satisfaction_sms_message_id')->nullable()->after('satisfaction_sms_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['reminder_sms_message_id', 'satisfaction_sms_message_id']);
        });
    }
};
