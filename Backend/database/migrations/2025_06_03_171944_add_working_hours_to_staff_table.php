<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('salon_staff', function (Blueprint $table) {

            if (!Schema::hasColumn('salon_staff', 'working_hours')) {
                $table->json('working_hours')->nullable()->after('is_active');
            }
        });
    }

    public function down()
    {
        Schema::table('salon_staff', function (Blueprint $table) {
            if (Schema::hasColumn('salon_staff', 'working_hours')) {
                $table->dropColumn('working_hours');
            }
        });
    }
};
