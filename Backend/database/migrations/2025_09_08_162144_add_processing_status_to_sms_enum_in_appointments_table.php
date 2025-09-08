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
        // Update reminder_sms_status enum to include 'processing'
        DB::statement("ALTER TABLE appointments MODIFY reminder_sms_status ENUM('not_sent', 'processing', 'pending', 'sent') NOT NULL DEFAULT 'not_sent'");
        
        // Update satisfaction_sms_status enum to include 'processing'
        DB::statement("ALTER TABLE appointments MODIFY satisfaction_sms_status ENUM('not_sent', 'processing', 'pending', 'sent') NOT NULL DEFAULT 'not_sent'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert reminder_sms_status enum to original values
        DB::statement("ALTER TABLE appointments MODIFY reminder_sms_status ENUM('not_sent', 'pending', 'sent') NOT NULL DEFAULT 'not_sent'");
        
        // Revert satisfaction_sms_status enum to original values  
        DB::statement("ALTER TABLE appointments MODIFY satisfaction_sms_status ENUM('not_sent', 'pending', 'sent') NOT NULL DEFAULT 'not_sent'");
    }
};
