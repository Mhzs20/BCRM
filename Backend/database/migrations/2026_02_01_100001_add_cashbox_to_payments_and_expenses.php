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
        Schema::table('payments_received', function (Blueprint $table) {
            if (!Schema::hasColumn('payments_received', 'cashbox_id')) {
                $table->unsignedBigInteger('cashbox_id')->nullable()->after('payment_method');
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'cashbox_id')) {
                $table->unsignedBigInteger('cashbox_id')->nullable()->after('related_payment_id');
            }
        });

        // Add foreign keys after columns exist
        try {
            Schema::table('payments_received', function (Blueprint $table) {
                $table->foreign('cashbox_id')->references('id')->on('cashboxes')->onDelete('set null');
                $table->index('cashbox_id');
            });
        } catch (\Exception $e) {
            // Foreign key might already exist
        }

        try {
            Schema::table('expenses', function (Blueprint $table) {
                $table->foreign('cashbox_id')->references('id')->on('cashboxes')->onDelete('set null');
                $table->index('cashbox_id');
            });
        } catch (\Exception $e) {
            // Foreign key might already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments_received', function (Blueprint $table) {
            $table->dropForeign(['cashbox_id']);
            $table->dropColumn('cashbox_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['cashbox_id']);
            $table->dropColumn('cashbox_id');
        });
    }
};
