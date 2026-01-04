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
            $table->enum('commission_type', ['percentage', 'fixed'])->default('percentage')->after('is_active');
            $table->decimal('commission_value', 8, 2)->default(0)->after('commission_type')->comment('Percentage (0-100) or Fixed amount');
            $table->decimal('total_commission_paid', 10, 2)->default(0)->after('total_income')->comment('Total commission paid to this staff');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salon_staff', function (Blueprint $table) {
            $table->dropColumn(['commission_type', 'commission_value', 'total_commission_paid']);
        });
    }
};
