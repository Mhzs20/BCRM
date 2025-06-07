<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::create('service_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('salon_staff')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['service_id', 'staff_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('service_staff');
    }
};
