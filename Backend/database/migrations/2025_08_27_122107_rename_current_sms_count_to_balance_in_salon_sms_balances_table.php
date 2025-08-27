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
        Schema::table('salon_sms_balances', function (Blueprint $table) {
            $table->renameColumn('current_sms_count', 'balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salon_sms_balances', function (Blueprint $table) {
            $table->renameColumn('balance', 'current_sms_count');
        });
    }
};
