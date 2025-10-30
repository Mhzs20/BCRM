<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('birthday_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salon_id');
            $table->unsignedBigInteger('template_id');
            $table->boolean('is_global_active')->default(true);
            $table->string('send_time', 5)->default('10:00');
            $table->timestamps();
            $table->foreign('salon_id')->references('id')->on('salons')->onDelete('cascade');
        });

        Schema::create('birthday_reminder_customer_group', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('birthday_reminder_id');
            $table->unsignedBigInteger('customer_group_id');
            $table->boolean('is_active')->default(true);
            $table->integer('send_days_before')->default(7);
            $table->timestamps();
            $table->foreign('birthday_reminder_id')->references('id')->on('birthday_reminders')->onDelete('cascade');
            $table->foreign('customer_group_id')->references('id')->on('customer_groups')->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('birthday_reminder_customer_group');
        Schema::dropIfExists('birthday_reminders');
    }
};
