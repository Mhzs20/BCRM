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
            $table->integer('sms_parts')->nullable()->after('content');
            $table->decimal('cost_per_sms', 10, 2)->nullable()->after('sms_parts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_transactions', function (Blueprint $table) {
            $table->dropColumn(['sms_parts', 'cost_per_sms']);
        });
    }
};
