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
            // Check if foreign key already exists by attempting to get constraint info
            $foreignKeys = collect(DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'wallet_transactions' 
                AND COLUMN_NAME = 'user_id' 
                AND REFERENCED_TABLE_NAME = 'users'
            "));
            
            if ($foreignKeys->isEmpty()) {
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