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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('discount_code')->nullable()->after('sms_package_id');
            $table->integer('discount_percentage')->nullable()->after('discount_code');
            $table->decimal('original_amount', 10, 2)->nullable()->after('amount'); // Store original price before discount
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['discount_code', 'discount_percentage', 'original_amount']);
        });
    }
};
