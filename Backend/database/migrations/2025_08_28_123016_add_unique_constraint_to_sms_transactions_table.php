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
        Schema::table('sms_transactions', function (Blueprint $table) {
            // Add reference_id column if it doesn't exist
            if (!Schema::hasColumn('sms_transactions', 'reference_id')) {
                $table->string('reference_id')->nullable()->after('transaction_id');
            }

            // Add unique constraints
            $table->unique('transaction_id');
            $table->unique('reference_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_transactions', function (Blueprint $table) {
            // Drop unique constraints
            $table->dropUnique(['transaction_id']);
            $table->dropUnique(['reference_id']);

            // Drop reference_id column if it exists
            if (Schema::hasColumn('sms_transactions', 'reference_id')) {
                $table->dropColumn('reference_id');
            }
        });
    }
};
