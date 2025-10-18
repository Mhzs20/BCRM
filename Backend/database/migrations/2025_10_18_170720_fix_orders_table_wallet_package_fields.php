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
            // Add only missing columns with proper placement
            if (!Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('orders', 'metadata')) {
                $table->json('metadata')->nullable();
            }
            if (!Schema::hasColumn('orders', 'payment_authority')) {
                $table->string('payment_authority')->nullable();
            }
            if (!Schema::hasColumn('orders', 'payment_ref_id')) {
                $table->string('payment_ref_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columns_to_drop = [];
            
            if (Schema::hasColumn('orders', 'paid_at')) {
                $columns_to_drop[] = 'paid_at';
            }
            if (Schema::hasColumn('orders', 'metadata')) {
                $columns_to_drop[] = 'metadata';
            }
            if (Schema::hasColumn('orders', 'payment_authority')) {
                $columns_to_drop[] = 'payment_authority';
            }
            if (Schema::hasColumn('orders', 'payment_ref_id')) {
                $columns_to_drop[] = 'payment_ref_id';
            }
            
            if (!empty($columns_to_drop)) {
                $table->dropColumn($columns_to_drop);
            }
        });
    }
};
