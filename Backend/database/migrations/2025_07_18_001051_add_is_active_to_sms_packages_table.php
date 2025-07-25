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
        Schema::table('sms_packages', function (Blueprint $table) {
            if (!Schema::hasColumn('sms_packages', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_packages', function (Blueprint $table) {
            if (Schema::hasColumn('sms_packages', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
