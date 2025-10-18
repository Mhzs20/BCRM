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
        Schema::create('wallet_packages', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // عنوان پکیج
            $table->text('description')->nullable(); // توضیحات
            $table->decimal('amount', 15, 0); // مبلغ شارژ (ریال)
            $table->decimal('price', 15, 0); // قیمت پکیج (ریال)
            $table->integer('discount_percentage')->default(0); // درصد تخفیف
            $table->boolean('is_active')->default(true); // وضعیت فعال/غیرفعال
            $table->boolean('is_featured')->default(false); // پکیج ویژه
            $table->integer('sort_order')->default(0); // ترتیب نمایش
            $table->string('icon')->nullable(); // آیکون پکیج
            $table->string('color', 7)->default('#3B82F6'); // رنگ پکیج
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_packages');
    }
};
