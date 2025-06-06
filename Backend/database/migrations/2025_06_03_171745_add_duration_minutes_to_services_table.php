<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'duration_minutes')) {
                $table->integer('duration_minutes')->unsigned()->default(30)->after('price');
            }
            if (!Schema::hasColumn('services', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('duration_minutes');
            }
        });
    }

    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'duration_minutes')) {
                $table->dropColumn('duration_minutes');
            }
            if (Schema::hasColumn('services', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
