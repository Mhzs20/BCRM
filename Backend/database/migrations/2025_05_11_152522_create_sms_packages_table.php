<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsPackagesTable extends Migration
{
    public function up()
    {
        Schema::create('sms_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('sms_count');
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sms_packages');
    }
}
