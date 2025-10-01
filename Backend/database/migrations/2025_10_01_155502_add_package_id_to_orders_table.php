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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'package_id')) {
                $table->unsignedBigInteger('package_id')->nullable()->after('sms_package_id');
            }
            
            if (!Schema::hasColumn('orders', 'type')) {
                $table->string('type')->default('sms')->after('package_id'); // 'sms' or 'feature'
            }
        });

        if (Schema::hasTable('packages') && Schema::hasColumn('orders', 'package_id')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->foreign('package_id')->references('id')->on('packages')->onDelete('set null');
                });
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            try {
                $table->dropForeign(['package_id']);
            } catch (\Exception $e) {
            }
            
            if (Schema::hasColumn('orders', 'package_id')) {
                $table->dropColumn('package_id');
            }
            if (Schema::hasColumn('orders', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
