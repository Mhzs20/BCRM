<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salon_sms_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('salon_sms_templates', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->after('salon_id');
            }
            if (!Schema::hasColumn('salon_sms_templates', 'title')) {
                $table->string('title')->nullable()->after('event_type');
            }
            if (!Schema::hasColumn('salon_sms_templates', 'template_type')) {
                $table->string('template_type')->default('system_event')->after('title')->comment('system_event | custom');
            }
            // make event_type nullable if it is not already
            try {
                $table->string('event_type')->nullable()->change();
            } catch (\Exception $e) {
                // Some drivers (like older SQLite) may not support change(); ignore silently.
            }
        });

        // Add FK in a separate step if possible
        try {
            Schema::table('salon_sms_templates', function (Blueprint $table) {
                $table->foreign('category_id')->references('id')->on('sms_template_categories')->nullOnDelete();
            });
        } catch (\Exception $e) {
            // Ignore FK creation errors gracefully.
        }
    }

    public function down(): void
    {
        Schema::table('salon_sms_templates', function (Blueprint $table) {
            if (Schema::hasColumn('salon_sms_templates', 'category_id')) {
                try { $table->dropForeign(['category_id']); } catch (\Exception $e) {}
                $table->dropColumn('category_id');
            }
            if (Schema::hasColumn('salon_sms_templates', 'title')) {
                $table->dropColumn('title');
            }
            if (Schema::hasColumn('salon_sms_templates', 'template_type')) {
                $table->dropColumn('template_type');
            }
            // We do not force event_type back to NOT NULL to avoid data loss.
        });
    }
};
