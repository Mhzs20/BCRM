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
        Schema::table('satisfaction_survey_logs', function (Blueprint $table) {
            $table->string('message_id')->nullable()->after('error_message')
                ->comment('Kavenegar message ID for tracking SMS status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('satisfaction_survey_logs', function (Blueprint $table) {
            $table->dropColumn('message_id');
        });
    }
};
