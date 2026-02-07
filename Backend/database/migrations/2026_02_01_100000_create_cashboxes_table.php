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
        Schema::create('cashboxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->string('name'); // نام صندوق: پوز ملت، صندوق نقدی، حساب بانک تجارت
            $table->string('type'); // cash, pos, bank_account, online
            $table->decimal('initial_balance', 15, 2)->default(0); // موجودی اولیه
            $table->decimal('current_balance', 15, 2)->default(0); // موجودی فعلی (محاسبه خودکار)
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0); // ترتیب نمایش
            $table->timestamps();
            $table->softDeletes();

            $table->index(['salon_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashboxes');
    }
};
