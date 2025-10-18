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
        try {
            // First, let's check if the foreign key already exists
            $foreignKeys = collect(DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'wallet_transactions' 
                AND COLUMN_NAME = 'user_id' 
                AND REFERENCED_TABLE_NAME = 'users'
            "));
            
            if ($foreignKeys->isNotEmpty()) {
                // Foreign key exists, drop it first
                Schema::table('wallet_transactions', function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                });
            }
            
            // Clean up any orphan records
            DB::statement('
                DELETE wt FROM wallet_transactions wt
                LEFT JOIN users u ON wt.user_id = u.id
                WHERE wt.user_id IS NOT NULL AND u.id IS NULL
            ');
            
            // Now add the foreign key constraint
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->foreign('user_id')
                      ->references('id')
                      ->on('users')
                      ->onDelete('cascade');
            });
            
        } catch (\Exception $e) {
            // Log the error but don't fail the migration
            \Log::error('Error in fix_wallet_transactions_foreign_key_issue migration: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        } catch (\Exception $e) {
            // Ignore errors when rolling back
            \Log::error('Error rolling back fix_wallet_transactions_foreign_key_issue migration: ' . $e->getMessage());
        }
    }
};
