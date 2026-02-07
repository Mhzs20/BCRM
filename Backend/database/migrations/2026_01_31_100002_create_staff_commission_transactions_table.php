<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * جدول تراکنش‌های پورسانت کارکنان
     */
    public function up(): void
    {
        Schema::create('staff_commission_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('salon_staff')->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null');
            
            // نوع تراکنش: commission = پورسانت، adjustment = اصلاح، payment = پرداخت
            $table->enum('transaction_type', ['commission', 'adjustment', 'payment'])->default('commission');
            
            // مبالغ
            $table->decimal('service_price', 12, 2)->default(0)->comment('قیمت خدمت');
            $table->decimal('discount_amount', 12, 2)->default(0)->comment('مبلغ تخفیف');
            $table->decimal('base_amount', 12, 2)->default(0)->comment('مبلغ پایه برای محاسبه پورسانت');
            $table->decimal('commission_rate', 8, 2)->default(0)->comment('نرخ پورسانت (درصد یا مبلغ)');
            $table->enum('commission_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('amount', 12, 2)->default(0)->comment('مبلغ نهایی پورسانت/اصلاح/پرداخت');
            
            // وضعیت پرداخت
            $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            
            // برای اصلاحات
            $table->text('description')->nullable();
            
            // برای ردیابی
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            // ایندکس‌ها برای گزارش‌گیری سریع
            $table->index(['salon_id', 'staff_id', 'payment_status'], 'sct_salon_staff_status_idx');
            $table->index(['salon_id', 'created_at'], 'sct_salon_created_idx');
            $table->index(['staff_id', 'created_at'], 'sct_staff_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_commission_transactions');
    }
};
