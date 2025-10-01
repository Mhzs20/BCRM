<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('sms_package_id')->nullable()->change();
            
            if (!Schema::hasColumn('orders', 'package_id')) {
                $table->foreignId('package_id')->nullable()->after('sms_package_id')
                    ->constrained('packages')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('orders', 'type')) {
                $table->string('type')->default('sms')->after('package_id'); // 'sms' or 'feature'
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn(['package_id', 'type']);
            $table->unsignedBigInteger('sms_package_id')->nullable(false)->change();
        });
    }
};
