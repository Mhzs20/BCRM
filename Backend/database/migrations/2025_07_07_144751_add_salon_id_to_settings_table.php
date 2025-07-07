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
        Schema::table('settings', function (Blueprint $table) {
            // Drop the old unique constraint if it exists
            $table->dropUnique('settings_key_unique');

            // Add the salon_id column
            $table->foreignId('salon_id')->nullable()->constrained('salons')->onDelete('cascade');

            // Add a new composite unique constraint
            $table->unique(['salon_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['salon_id', 'key']);
            $table->dropForeign(['salon_id']);
            $table->dropColumn('salon_id');
            $table->unique('key');
        });
    }
};
