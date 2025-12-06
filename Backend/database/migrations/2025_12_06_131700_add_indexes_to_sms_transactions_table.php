<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('sms_transactions', 'batch_id')) {
                return; // Safety: table should already have batch_id
            }

            $table->index('batch_id', 'sms_transactions_batch_id_idx');
            $table->index(['status', 'approval_status'], 'sms_transactions_status_approval_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sms_transactions', function (Blueprint $table) {
            $table->dropIndex('sms_transactions_batch_id_idx');
            $table->dropIndex('sms_transactions_status_approval_idx');
        });
    }
};
