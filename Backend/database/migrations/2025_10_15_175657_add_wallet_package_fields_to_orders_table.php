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
            // Add only missing columns
            if (!Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('orders', 'metadata')) {
                $table->json('metadata')->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('orders', 'payment_authority')) {
                $table->string('payment_authority')->nullable()->after('metadata');
            }
            if (!Schema::hasColumn('orders', 'payment_ref_id')) {
                $table->string('payment_ref_id')->nullable()->after('payment_authority');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'paid_at',
                'metadata',
                'payment_authority',
                'payment_ref_id'
            ]);
        });
    }
};
