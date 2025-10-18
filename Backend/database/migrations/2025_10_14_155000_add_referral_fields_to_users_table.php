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
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 20)->unique()->nullable()->after('last_login_at')->comment('کد دعوت کاربر');
            $table->foreignId('referrer_id')->nullable()->after('referral_code')->constrained('users')->onDelete('set null')->comment('کاربر معرف');
            $table->decimal('wallet_balance', 15, 0)->default(0)->after('referrer_id')->comment('موجودی کیف پول');
            
            // Index for performance
            $table->index(['referral_code']);
            $table->index(['referrer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referrer_id']);
            $table->dropIndex(['referral_code']);
            $table->dropIndex(['referrer_id']);
            $table->dropColumn(['referral_code', 'referrer_id', 'wallet_balance']);
        });
    }
};
