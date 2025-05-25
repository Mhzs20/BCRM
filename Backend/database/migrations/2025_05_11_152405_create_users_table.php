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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mobile', 11)->unique();
            $table->string('password')->nullable();
            $table->string('avatar')->nullable();
            $table->string('otp_code', 4)->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->string('business_name')->nullable();
            $table->unsignedBigInteger('business_category_id')->nullable();
            $table->unsignedBigInteger('business_subcategory_id')->nullable();
            $table->unsignedBigInteger('active_salon_id')->nullable();
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};