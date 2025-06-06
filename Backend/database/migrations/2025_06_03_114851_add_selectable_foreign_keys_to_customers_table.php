<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {


            if (!Schema::hasColumn('customers', 'how_introduced_id')) {
                $table->foreignId('how_introduced_id')->nullable()->after('address')
                ->constrained('how_introduceds')->nullOnDelete();
            }
            if (!Schema::hasColumn('customers', 'customer_group_id')) {
                $table->foreignId('customer_group_id')->nullable()->after('how_introduced_id')
                    ->constrained('customer_groups')->nullOnDelete();
            }
            if (!Schema::hasColumn('customers', 'job_id')) {
                $table->foreignId('job_id')->nullable()->after('customer_group_id')
                    ->constrained('jobs')->nullOnDelete();
            }
            if (!Schema::hasColumn('customers', 'age_range_id')) {
                $table->foreignId('age_range_id')->nullable()->after('job_id')
                    ->constrained('age_ranges')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'age_range_id')) {
                $table->dropForeign(['age_range_id']);
                $table->dropColumn('age_range_id');
            }
            if (Schema::hasColumn('customers', 'job_id')) {
                $table->dropForeign(['job_id']);
                $table->dropColumn('job_id');
            }
            if (Schema::hasColumn('customers', 'customer_group_id')) {
                $table->dropForeign(['customer_group_id']);
                $table->dropColumn('customer_group_id');
            }
            if (Schema::hasColumn('customers', 'how_introduced_id')) {
                $table->dropForeign(['how_introduced_id']);
                $table->dropColumn('how_introduced_id');
            }
        });
    }
};
