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
            // User filter fields
            $table->json('target_users')->nullable()->comment('JSON containing user filter criteria');
            $table->enum('user_filter_type', ['all', 'filtered'])->default('all')->comment('Type of user targeting');
            $table->text('description')->nullable()->comment('Description of the discount code');
            $table->integer('usage_limit')->nullable()->comment('Maximum number of times this code can be used');
            $table->integer('usage_count')->default(0)->comment('Number of times this code has been used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->dropColumn([
                'target_users',
                'user_filter_type', 
                'description',
                'usage_limit',
                'usage_count'
            ]);
        });
    }
};
