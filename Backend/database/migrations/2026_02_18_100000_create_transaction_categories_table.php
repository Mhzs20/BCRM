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
        // جدول دسته‌بندی‌های تراکنش
        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->string('name'); // نام دسته‌بندی
            $table->string('type')->default('both'); // both, income, expense
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // آیا سیستمی است (خدمات)
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['salon_id', 'is_active'], 'tc_salon_active_idx');
            $table->index(['salon_id', 'type'], 'tc_salon_type_idx');
        });

        // جدول زیردسته‌های تراکنش
        Schema::create('transaction_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('transaction_categories')->onDelete('cascade');
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->string('name'); // نام زیردسته
            $table->text('description')->nullable();
            
            // برای لینک به خدمات سالن (اختیاری)
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('cascade');
            
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'is_active'], 'ts_category_active_idx');
            $table->index(['salon_id', 'service_id'], 'ts_salon_service_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_subcategories');
        Schema::dropIfExists('transaction_categories');
    }
};
