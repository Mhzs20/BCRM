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
        Schema::create('admin_otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('mobile');
            $table->string('otp_code');
            $table->string('temp_token')->nullable()->unique(); // توکن موقت بعد از تایید OTP
            $table->boolean('is_verified')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['mobile', 'is_verified']);
            $table->index('temp_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_otp_verifications');
    }
};
