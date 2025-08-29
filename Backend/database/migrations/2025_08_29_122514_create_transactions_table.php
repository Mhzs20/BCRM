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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('gateway'); // e.g., zarinpal, mellat
            $table->decimal('amount', 10, 2);
            $table->string('transaction_id')->nullable()->unique(); // Gateway's Authority/Transaction ID
            $table->string('reference_id')->nullable(); // Gateway's Reference ID after successful verification
            $table->string('status')->default('pending'); // e.g., pending, completed, failed, expired
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
