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
        Schema::create('notification_salon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notifications')->onDelete('cascade');
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_salon');
    }
};
