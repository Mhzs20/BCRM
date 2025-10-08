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
        // repair_date فیلد قبلاً وجود دارد، فقط اطمینان حاصل می‌کنیم که هست
        if (!Schema::hasColumn('appointments', 'repair_date')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->date('repair_date')->nullable()->after('appointment_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('appointments', 'repair_date')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropColumn('repair_date');
            });
        }
    }
};