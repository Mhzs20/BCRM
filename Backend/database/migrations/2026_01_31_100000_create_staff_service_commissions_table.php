<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * جدول پورسانت اختصاصی هر خدمت برای هر کارکن
     */
    public function up(): void
    {
        Schema::create('staff_service_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('salon_staff')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->enum('commission_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('commission_value', 10, 2)->default(0)->comment('درصد (0-100) یا مبلغ ثابت');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['staff_id', 'service_id'], 'staff_service_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_service_commissions');
    }
};
