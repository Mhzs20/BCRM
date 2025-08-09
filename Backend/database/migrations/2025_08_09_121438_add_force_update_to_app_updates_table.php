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
        Schema::table('app_updates', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('app_store_link');
            $table->boolean('force_update')->default(false)->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_updates', function (Blueprint $table) {
            $table->dropColumn('notes');
            $table->dropColumn('force_update');
        });
    }
};
