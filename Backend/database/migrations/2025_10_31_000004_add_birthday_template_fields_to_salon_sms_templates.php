<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('salon_sms_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('salon_sms_templates', 'title')) {
                $table->string('title')->nullable();
            }
            if (!Schema::hasColumn('salon_sms_templates', 'estimated_parts')) {
                $table->integer('estimated_parts')->nullable();
            }
            if (!Schema::hasColumn('salon_sms_templates', 'estimated_cost')) {
                $table->integer('estimated_cost')->nullable();
            }
            if (!Schema::hasColumn('salon_sms_templates', 'variables')) {
                $table->json('variables')->nullable();
            }
        });
    }

    public function down() {
        Schema::table('salon_sms_templates', function (Blueprint $table) {
            $table->dropColumn(['title', 'estimated_parts', 'estimated_cost', 'variables']);
        });
    }
};
