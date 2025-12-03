<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update sms_type for purchase transactions that don't have it
        DB::statement("
            UPDATE sms_transactions 
            SET sms_type = 'purchase'
            WHERE type = 'purchase' 
            AND (sms_type IS NULL OR sms_type = '')
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this
    }
};
