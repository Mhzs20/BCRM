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
        Schema::create('cashbox_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            
            // نوع تراکنش: income (دریافتی), expense (پرداختی), transfer (انتقال)
            $table->enum('type', ['income', 'expense', 'transfer']);
            
            // برای income و expense
            $table->foreignId('cashbox_id')->nullable()->constrained('cashboxes')->onDelete('cascade');
            
            // برای transfer (انتقال بین دو صندوق)
            $table->foreignId('from_cashbox_id')->nullable()->constrained('cashboxes')->onDelete('cascade');
            $table->foreignId('to_cashbox_id')->nullable()->constrained('cashboxes')->onDelete('cascade');
            
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // دسته‌بندی (برای income/expense)
            $table->string('subcategory')->nullable(); // زیردسته
            
            // ارجاعات به تراکنش‌های اصلی
            $table->foreignId('payment_id')->nullable()->constrained('payments_received')->onDelete('set null');
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->onDelete('set null');
            $table->foreignId('commission_transaction_id')->nullable()->constrained('staff_commission_transactions')->onDelete('set null');
            
            $table->date('transaction_date'); // تاریخ میلادی
            $table->string('transaction_time')->nullable(); // ساعت تراکنش
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['salon_id', 'type', 'transaction_date'], 'cbt_salon_type_date_idx');
            $table->index(['cashbox_id', 'transaction_date'], 'cbt_cashbox_date_idx');
            $table->index(['from_cashbox_id', 'to_cashbox_id'], 'cbt_transfer_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashbox_transactions');
    }
};
