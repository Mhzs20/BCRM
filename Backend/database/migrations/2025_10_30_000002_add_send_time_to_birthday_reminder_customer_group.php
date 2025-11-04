<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('birthday_reminder_customer_group', function (Blueprint $table) {
            $table->string('send_time', 5)->default('10:00')->after('send_days_before');
        });
    }

    public function down() {
        Schema::table('birthday_reminder_customer_group', function (Blueprint $table) {
            $table->dropColumn('send_time');
        });
    }
};
