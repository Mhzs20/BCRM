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
            // Add generic fields for different order types
            if (!Schema::hasColumn('orders', 'item_id')) {
                $table->unsignedBigInteger('item_id')->nullable()->after('type');
            }
            if (!Schema::hasColumn('orders', 'item_title')) {
                $table->string('item_title')->nullable()->after('item_id');
            }
            if (!Schema::hasColumn('orders', 'original_amount')) {
                $table->decimal('original_amount', 15, 0)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 0)->default(0)->after('original_amount');
            }
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method')->default('online')->after('status');
            }
            if (!Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('orders', 'discount_code')) {
                $table->string('discount_code')->nullable()->after('discount_amount');
            }
            if (!Schema::hasColumn('orders', 'discount_percentage')) {
                $table->integer('discount_percentage')->default(0)->after('discount_code');
            }
            if (!Schema::hasColumn('orders', 'transaction_id')) {
                $table->string('transaction_id')->nullable()->after('payment_ref_id');
            }
            if (!Schema::hasColumn('orders', 'description')) {
                $table->text('description')->nullable()->after('metadata');
            }
            
            // Make salon_id nullable for wallet packages (not tied to a specific salon)
            $table->unsignedBigInteger('salon_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'item_id',
                'item_title',
                'original_amount',
                'discount_amount',
                'payment_method',
                'payment_status',
                'discount_code',
                'discount_percentage',
                'transaction_id',
                'description'
            ]);
        });
    }
};
