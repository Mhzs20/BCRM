<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First update existing data to compatible value
        DB::table('referral_settings')->update(['reward_type' => 'fixed_amount']);
        
        Schema::table('referral_settings', function (Blueprint $table) {
            // Add new columns only
            if (!Schema::hasColumn('referral_settings', 'link_expiry_days')) {
                $table->integer('link_expiry_days')->default(365);
            }
            if (!Schema::hasColumn('referral_settings', 'max_referrals_per_month')) {
                $table->integer('max_referrals_per_month')->default(0);
            }
            if (!Schema::hasColumn('referral_settings', 'min_purchase_amount')) {
                $table->decimal('min_purchase_amount', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('referral_settings', 'send_sms_notifications')) {
                $table->boolean('send_sms_notifications')->default(false);
            }
        });
        
        // Add new columns with proper names for backward compatibility
        if (!Schema::hasColumn('referral_settings', 'referral_reward_amount')) {
            Schema::table('referral_settings', function (Blueprint $table) {
                $table->decimal('referral_reward_amount', 15, 0)->default(10000);
            });
        }
        
        if (!Schema::hasColumn('referral_settings', 'order_reward_percentage')) {
            Schema::table('referral_settings', function (Blueprint $table) {
                $table->decimal('order_reward_percentage', 5, 2)->default(5.0);
            });
        }
        
        if (!Schema::hasColumn('referral_settings', 'max_order_reward')) {
            Schema::table('referral_settings', function (Blueprint $table) {
                $table->decimal('max_order_reward', 15, 0)->nullable();
            });
        }
        
        // Update reward_type enum values last
        Schema::table('referral_settings', function (Blueprint $table) {
            $table->enum('reward_type', ['fixed_amount', 'purchase_percentage'])->default('fixed_amount')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referral_settings', function (Blueprint $table) {
            $table->dropColumn([
                'link_expiry_days', 'max_referrals_per_month', 
                'min_purchase_amount', 'send_sms_notifications',
                'referral_reward_amount', 'order_reward_percentage', 'max_order_reward'
            ]);
        });
    }
};
