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
        Schema::create('referral_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('signup_reward', 15, 0)->default(0)->comment('پاداش ثبت‌نام موفق');
            $table->decimal('purchase_percentage', 5, 2)->default(0)->comment('درصد پاداش از خرید');
            $table->integer('monthly_referral_limit')->default(10)->comment('سقف دعوت موفق در ماه');
            $table->decimal('minimum_withdrawal_amount', 15, 0)->default(50000)->comment('حداقل مبلغ برداشت');
            $table->boolean('is_active')->default(false)->comment('فعال بودن سیستم رفرال');
            $table->string('reward_type')->default('cash')->comment('نوع پاداش (cash, points, discount)');
            $table->decimal('max_purchase_reward_amount', 15, 0)->nullable()->comment('حداکثر مبلغ پاداش خرید');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_settings');
    }
};
