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
            $table->dropColumn('business_subcategory_id');
            $table->json('business_subcategory_ids')->nullable()->after('business_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('business_subcategory_id')->nullable()->after('business_category_id');
            $table->foreign('business_subcategory_id')->references('id')->on('business_subcategories')->onDelete('set null');
            $table->dropColumn('business_subcategory_ids');
        });
    }
};
