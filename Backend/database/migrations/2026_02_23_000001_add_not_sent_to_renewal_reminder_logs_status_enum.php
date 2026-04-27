<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `renewal_reminder_logs` MODIFY COLUMN `status` ENUM('pending', 'sent', 'failed', 'not_sent') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Note: rows with 'not_sent' will be set to 'pending' before reverting
        DB::statement("UPDATE `renewal_reminder_logs` SET `status` = 'failed' WHERE `status` = 'not_sent'");
        DB::statement("ALTER TABLE `renewal_reminder_logs` MODIFY COLUMN `status` ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending'");
    }
};
