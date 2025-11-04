<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('birthday_reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('birthday_reminders', 'send_days_before')) {
                $table->integer('send_days_before')->default(3)->after('send_time');
            }
        });
    }
    public function down() {
        Schema::table('birthday_reminders', function (Blueprint $table) {
            if (Schema::hasColumn('birthday_reminders', 'send_days_before')) {
                $table->dropColumn('send_days_before');
            }
        });
    }
};
