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
        Schema::table('referral_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('referral_settings', 'daily_referral_limit')) {
                $table->integer('daily_referral_limit')->default(0)->after('max_referrals_per_month')->comment('حداکثر دعوت در روز (0 = نامحدود)');
            }
            if (!Schema::hasColumn('referral_settings', 'total_referral_limit')) {
                $table->integer('total_referral_limit')->default(0)->after('daily_referral_limit')->comment('حداکثر دعوت کل (0 = نامحدود)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referral_settings', function (Blueprint $table) {
            if (Schema::hasColumn('referral_settings', 'daily_referral_limit')) {
                $table->dropColumn('daily_referral_limit');
            }
            if (Schema::hasColumn('referral_settings', 'total_referral_limit')) {
                $table->dropColumn('total_referral_limit');
            }
        });
    }
};
