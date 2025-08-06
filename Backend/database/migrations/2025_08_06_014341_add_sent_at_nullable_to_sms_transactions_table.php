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
            $table->timestamp('sent_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_transactions', function (Blueprint $table) {
            // Revert to non-nullable, assuming it was originally non-nullable
            // This might require a default value if there are existing nulls,
            // but for this specific fix, we're just reverting the change.
            $table->timestamp('sent_at')->nullable(false)->change();
        });
    }
};
