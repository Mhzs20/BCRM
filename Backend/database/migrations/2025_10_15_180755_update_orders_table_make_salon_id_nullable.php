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
            // Make salon_id nullable for wallet charges
            $table->unsignedBigInteger('salon_id')->nullable()->change();

            // Add new fields for wallet packages
            if (!Schema::hasColumn('orders', 'order_type')) {
                $table->string('order_type')->nullable()->after('type');
            }
            if (!Schema::hasColumn('orders', 'transaction_id')) {
                $table->string('transaction_id')->nullable();
            }
            if (!Schema::hasColumn('orders', 'description')) {
                $table->text('description')->nullable()->after('metadata');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('salon_id')->nullable(false)->change();
            $table->dropColumn(['order_type', 'transaction_id', 'description']);
        });
    }
};
