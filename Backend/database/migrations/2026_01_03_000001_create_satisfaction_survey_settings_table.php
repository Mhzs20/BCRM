<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('satisfaction_survey_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salon_id');
            $table->unsignedBigInteger('template_id');
            $table->boolean('is_global_active')->default(true);
            $table->timestamps();
            $table->foreign('salon_id')->references('id')->on('salons')->onDelete('cascade');
        });

        Schema::create('satisfaction_survey_group_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satisfaction_survey_setting_id');
            $table->unsignedBigInteger('customer_group_id');
            $table->boolean('is_active')->default(true);
            $table->integer('send_hours_after')->default(2); // 2, 4, 6, 8, 10, 12, 24
            $table->timestamps();
            $table->foreign('satisfaction_survey_setting_id', 'sss_id_foreign')->references('id')->on('satisfaction_survey_settings')->onDelete('cascade');
            $table->foreign('customer_group_id')->references('id')->on('customer_groups')->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('satisfaction_survey_group_settings');
        Schema::dropIfExists('satisfaction_survey_settings');
    }
};
