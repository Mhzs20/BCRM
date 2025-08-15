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
        Schema::create('salon_business_subcategory', function (Blueprint $table) {
            $table->foreignId('salon_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_subcategory_id')->constrained()->onDelete('cascade');
            $table->primary(['salon_id', 'business_subcategory_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salon_business_subcategory');
    }
};
