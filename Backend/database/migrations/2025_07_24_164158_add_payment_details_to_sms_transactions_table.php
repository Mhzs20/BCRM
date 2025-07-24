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
        Schema::table('sms_transactions', function (Blueprint $table) {
            $table->foreignId('sms_package_id')->nullable()->constrained('sms_packages')->after('salon_id');
            $table->decimal('amount', 10, 2)->nullable()->after('sms_package_id');
            $table->string('transaction_id')->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_transactions', function (Blueprint $table) {
            $table->dropForeign(['sms_package_id']);
            $table->dropColumn(['sms_package_id', 'amount', 'transaction_id']);
        });
    }
};
