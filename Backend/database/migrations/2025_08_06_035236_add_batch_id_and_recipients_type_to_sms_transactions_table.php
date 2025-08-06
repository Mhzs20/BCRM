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
            $table->uuid('batch_id')->nullable()->after('id');
            $table->string('recipients_type')->nullable()->after('receptor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_transactions', function (Blueprint $table) {
            $table->dropColumn('batch_id');
            $table->dropColumn('recipients_type');
        });
    }
};
