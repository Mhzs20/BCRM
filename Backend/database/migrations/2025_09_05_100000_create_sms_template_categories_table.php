<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_template_categories', function (Blueprint $table) {
            // Ensure FK support
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
            $table->unique(['salon_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_template_categories');
    }
};
