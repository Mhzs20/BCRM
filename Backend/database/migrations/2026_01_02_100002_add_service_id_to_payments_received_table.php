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
        Schema::table('payments_received', function (Blueprint $table) {
            $table->foreignId('staff_id')->nullable()->after('appointment_id')->constrained('salon_staff')->onDelete('set null');
            $table->string('payment_method')->nullable()->after('amount')->comment('cash, card, transfer, etc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments_received', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
            $table->dropColumn(['staff_id', 'payment_method']);
        });
    }
};
