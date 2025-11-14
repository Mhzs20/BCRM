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
        Schema::table('discount_codes', function (Blueprint $table) {
            // Add type and value columns to replace percentage
            $table->string('type')->default('percentage')->after('code'); // percentage or fixed
            $table->decimal('value', 10, 2)->default(0)->after('type'); // percentage value or fixed amount
            
            // Add other missing columns
            $table->decimal('min_order_amount', 10, 2)->nullable()->after('description');
            $table->decimal('max_discount_amount', 10, 2)->nullable()->after('min_order_amount');
            $table->timestamp('starts_at')->nullable()->after('max_discount_amount');
            
            // Rename usage_count to used_count for consistency
            if (Schema::hasColumn('discount_codes', 'usage_count')) {
                $table->renameColumn('usage_count', 'used_count');
            }
        });
        
        // Migrate existing percentage data to new structure
        DB::statement("UPDATE discount_codes SET type = 'percentage', value = percentage WHERE percentage IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'value',
                'min_order_amount',
                'max_discount_amount',
                'starts_at'
            ]);
            
            // Rename back
            if (Schema::hasColumn('discount_codes', 'used_count')) {
                $table->renameColumn('used_count', 'usage_count');
            }
        });
    }
};
