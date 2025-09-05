<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make salon_id nullable in both tables to allow global templates/categories
        try {
            Schema::table('sms_template_categories', function (Blueprint $table) {
                $table->unsignedBigInteger('salon_id')->nullable()->change();
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('salon_sms_templates', function (Blueprint $table) {
                $table->unsignedBigInteger('salon_id')->nullable()->change();
            });
        } catch (\Exception $e) {}
    }

    public function down(): void
    {
        // Not forcing back to NOT NULL (risk of data loss); left intentionally.
    }
};
