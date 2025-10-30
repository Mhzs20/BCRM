<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('salon_sms_templates', function (Blueprint $table) {
            $table->string('title')->nullable();
            $table->integer('estimated_parts')->nullable();
            $table->integer('estimated_cost')->nullable();
            $table->json('variables')->nullable();
        });
    }

    public function down() {
        Schema::table('salon_sms_templates', function (Blueprint $table) {
            $table->dropColumn(['title', 'estimated_parts', 'estimated_cost', 'variables']);
        });
    }
};
