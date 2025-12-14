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
        Schema::table('professions', function (Blueprint $table) {
            $table->foreignId('salon_id')->nullable()->change();
        });

        Schema::table('how_introduceds', function (Blueprint $table) {
            $table->foreignId('salon_id')->nullable()->change();
        });

        Schema::table('customer_groups', function (Blueprint $table) {
            $table->foreignId('salon_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We cannot easily revert this to not null if there are null values, 
        // but for the sake of migration structure:
        Schema::table('professions', function (Blueprint $table) {
            $table->foreignId('salon_id')->nullable(false)->change();
        });

        Schema::table('how_introduceds', function (Blueprint $table) {
            $table->foreignId('salon_id')->nullable(false)->change();
        });

        Schema::table('customer_groups', function (Blueprint $table) {
            $table->foreignId('salon_id')->nullable(false)->change();
        });
    }
};
