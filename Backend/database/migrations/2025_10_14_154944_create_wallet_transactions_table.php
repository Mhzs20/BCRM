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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('کاربر مالک کیف پول');
            $table->string('type')->comment('نوع تراکنش');
            $table->decimal('amount', 15, 0)->comment('مبلغ تراکنش (مثبت: واریز، منفی: برداشت)');
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed')->comment('وضعیت تراکنش');
            $table->text('description')->comment('توضیح تراکنش');
            $table->string('transaction_id')->nullable()->comment('شناسه تراکنش خارجی');
            $table->foreignId('referral_id')->nullable()->constrained('user_referrals')->onDelete('set null')->comment('مرجع رفرال');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null')->comment('مرجع سفارش');
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null')->comment('ادمین انجام‌دهنده');
            $table->json('metadata')->nullable()->comment('اطلاعات اضافی');
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
