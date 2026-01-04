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
        Schema::create('appointment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->text('notes')->nullable()->comment('یادداشت توضیحی درباره جزئیات خدمات');
            $table->json('images')->nullable()->comment('آرایه مسیرهای تصاویر (حداکثر 4 عکس)');
            $table->timestamps();

            // Indexes for performance
            $table->index(['appointment_id']);
            $table->index(['customer_id', 'salon_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_attachments');
    }
};
