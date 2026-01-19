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
            $table->enum('confirmation_sms_status', ['not_sent', 'processing', 'pending', 'sent'])
                ->default('not_sent')
                ->after('send_confirmation_sms');
            $table->string('confirmation_sms_message_id')->nullable()->after('confirmation_sms_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['confirmation_sms_status', 'confirmation_sms_message_id']);
        });
    }
};
