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
        // Clean up orphan wallet transactions
        DB::statement('
            DELETE wt FROM wallet_transactions wt
            LEFT JOIN users u ON wt.user_id = u.id
            WHERE wt.user_id IS NOT NULL AND u.id IS NULL
        ');
        
        // Add foreign key constraint to prevent future orphan records
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // First, ensure user_id is properly indexed
            if (!Schema::hasColumn('wallet_transactions', 'user_id_foreign')) {
                $table->foreign('user_id')
                      ->references('id')
                      ->on('users')
                      ->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }
};