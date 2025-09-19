<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Copy mobile from users to salons where salon mobile is null
        DB::statement('
            UPDATE salons
            INNER JOIN users ON salons.user_id = users.id
            SET salons.mobile = users.mobile
            WHERE salons.mobile IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this data migration
    }
};
