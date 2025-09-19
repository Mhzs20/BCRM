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
        Schema::table('salon_staff', function (Blueprint $table) {
            $table->integer('total_appointments')->default(0)->after('hire_date');
            $table->integer('completed_appointments')->default(0)->after('total_appointments');
            $table->integer('canceled_appointments')->default(0)->after('completed_appointments');
            $table->decimal('total_income', 15, 2)->default(0)->after('canceled_appointments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salon_staff', function (Blueprint $table) {
            $table->dropColumn(['total_appointments', 'completed_appointments', 'canceled_appointments', 'total_income']);
        });
    }
};
