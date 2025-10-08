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
        Schema::table('renewal_reminder_logs', function (Blueprint $table) {
            $table->foreignId('service_id')->after('salon_id')->constrained('services')->onDelete('cascade');
        });
    }

    /**admin/salons/48
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('renewal_reminder_logs', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
        });
    }
};
