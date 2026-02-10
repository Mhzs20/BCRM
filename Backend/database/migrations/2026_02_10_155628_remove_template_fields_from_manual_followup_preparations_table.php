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
        Schema::table('manual_followup_preparations', function (Blueprint $table) {
            // حذف فیلدهای مربوط به template که دیگر در prepare لازم نیست
            $table->dropColumn([
                'message_parts',
                'cost_per_message',
                'total_cost',
                'sample_message'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manual_followup_preparations', function (Blueprint $table) {
            // بازگردانی فیلدها در صورت rollback
            $table->integer('message_parts')->default(1);
            $table->integer('cost_per_message');
            $table->integer('total_cost');
            $table->text('sample_message')->nullable();
        });
    }
};
