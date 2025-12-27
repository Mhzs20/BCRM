<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('services')->update(['is_online_bookable' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally, set back to false, but since default is now true, maybe not needed
        // DB::table('services')->update(['is_online_bookable' => false]);
    }
};
