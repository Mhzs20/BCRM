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
        Schema::table('customer_feedback', function (Blueprint $table) {
            $table->foreignId('staff_id')->nullable()->after('appointment_id')->constrained('salon_staff')->onDelete('set null');
            $table->foreignId('service_id')->nullable()->after('staff_id')->constrained('services')->onDelete('set null');
            $table->boolean('is_submitted')->default(false)->after('weaknesses_selected');
            $table->timestamp('submitted_at')->nullable()->after('is_submitted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_feedback', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
            $table->dropForeign(['service_id']);
            $table->dropColumn(['staff_id', 'service_id', 'is_submitted', 'submitted_at']);
        });
    }
};
