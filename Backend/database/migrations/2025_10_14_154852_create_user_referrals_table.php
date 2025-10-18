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
        Schema::create('user_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade')->comment('کاربر دعوت‌کننده');
            $table->foreignId('referred_id')->constrained('users')->onDelete('cascade')->comment('کاربر دعوت‌شده');
            $table->enum('status', ['pending', 'registered', 'purchased', 'rewarded', 'cancelled'])->default('pending')->comment('وضعیت دعوت');
            $table->decimal('signup_reward_amount', 15, 0)->default(0)->comment('مبلغ پاداش ثبت‌نام');
            $table->decimal('purchase_reward_amount', 15, 0)->default(0)->comment('مبلغ پاداش خرید');
            $table->decimal('total_reward_amount', 15, 0)->default(0)->comment('مجموع پاداش‌ها');
            $table->timestamp('registered_at')->nullable()->comment('زمان ثبت‌نام معرفی‌شده');
            $table->timestamp('first_purchase_at')->nullable()->comment('زمان اولین خرید');
            $table->timestamp('last_purchase_at')->nullable()->comment('زمان آخرین خرید');
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint to prevent duplicate referrals
            $table->unique(['referrer_id', 'referred_id'], 'unique_referral');
            
            // Index for performance
            $table->index(['referrer_id', 'status']);
            $table->index(['referred_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_referrals');
    }
};
