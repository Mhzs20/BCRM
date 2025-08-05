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
        Schema::table('appointment_service', function (Blueprint $table) {
            if (Schema::hasColumn('appointment_service', 'duration_at_booking')) {
                $table->dropColumn('duration_at_booking');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_service', function (Blueprint $table) {
            $table->integer('duration_at_booking')->nullable();
        });
    }
};
