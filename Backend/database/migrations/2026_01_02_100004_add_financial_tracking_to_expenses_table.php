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
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('expense_type')->default('general')->after('category')->comment('general, commission, salary, supplies, etc');
            $table->foreignId('staff_id')->nullable()->after('expense_type')->constrained('salon_staff')->onDelete('set null')->comment('For commission or staff-related expenses');
            $table->foreignId('related_payment_id')->nullable()->after('staff_id')->constrained('payments_received')->onDelete('set null')->comment('Link to income if this is a commission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
            $table->dropForeign(['related_payment_id']);
            $table->dropColumn(['expense_type', 'staff_id', 'related_payment_id']);
        });
    }
};
