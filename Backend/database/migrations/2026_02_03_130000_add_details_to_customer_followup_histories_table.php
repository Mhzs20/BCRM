<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_followup_histories', function (Blueprint $table) {
            $table->json('customer_group_ids')->nullable()->after('type');
            $table->json('service_ids')->nullable()->after('customer_group_ids');
            $table->integer('total_customers')->nullable()->after('service_ids');
            $table->integer('sms_count')->nullable()->after('total_customers');
        });
    }

    public function down(): void
    {
        Schema::table('customer_followup_histories', function (Blueprint $table) {
            $table->dropColumn(['customer_group_ids', 'service_ids', 'total_customers', 'sms_count']);
        });
    }
};
