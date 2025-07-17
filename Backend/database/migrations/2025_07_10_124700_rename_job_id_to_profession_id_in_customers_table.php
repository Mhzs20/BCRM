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
        Schema::table('customers', function (Blueprint $table) {
            // Drop foreign key constraint before renaming
            $table->dropForeign(['job_id']);
            $table->renameColumn('job_id', 'profession_id');
            // Add foreign key constraint back with the new column name
            $table->foreign('profession_id')->references('id')->on('professions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Drop foreign key constraint before renaming back
            $table->dropForeign(['profession_id']);
            $table->renameColumn('profession_id', 'job_id');
            // Add foreign key constraint back with the old column name
            $table->foreign('job_id')->references('id')->on('jobs')->nullOnDelete();
        });
    }
};
