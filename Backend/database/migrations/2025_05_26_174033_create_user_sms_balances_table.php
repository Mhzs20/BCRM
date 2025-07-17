<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sms_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->integer('balance')->default(0)->comment('تعداد پیامک باقی‌مانده کاربر');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sms_balances');
    }
};
