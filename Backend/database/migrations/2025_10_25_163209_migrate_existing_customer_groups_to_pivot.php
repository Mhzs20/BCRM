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
        // Migrate existing customer_group_id to pivot table
        DB::statement('
            INSERT INTO customer_customer_group (customer_id, customer_group_id, salon_id, created_at, updated_at)
            SELECT c.id, c.customer_group_id, c.salon_id, NOW(), NOW()
            FROM customers c
            WHERE c.customer_group_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove migrated data
        DB::statement('DELETE FROM customer_customer_group');
    }
};
