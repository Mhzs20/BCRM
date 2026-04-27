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
        Schema::table('cashbox_transactions', function (Blueprint $table) {
            // اضافه کردن foreign key به دسته‌بندی و زیردسته
            $table->foreignId('category_id')->nullable()->after('description')->constrained('transaction_categories')->onDelete('set null');
            $table->foreignId('subcategory_id')->nullable()->after('category_id')->constrained('transaction_subcategories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashbox_transactions', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['subcategory_id']);
            $table->dropColumn(['category_id', 'subcategory_id']);
        });
    }
};
