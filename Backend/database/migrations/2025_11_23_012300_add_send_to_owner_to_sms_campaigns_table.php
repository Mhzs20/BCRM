<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('sms_campaigns', function (Blueprint $table) {
            $table->boolean('send_to_owner')->default(false)->after('message');
        });
    }

    public function down()
    {
        Schema::table('sms_campaigns', function (Blueprint $table) {
            $table->dropColumn('send_to_owner');
        });
    }
};
