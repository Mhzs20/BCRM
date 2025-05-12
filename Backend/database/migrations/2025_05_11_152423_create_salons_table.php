<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalonsTable extends Migration
{
    public function up()
    {
        Schema::create('salons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('phone_number', 11)->nullable();
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('business_category')->nullable();
            $table->string('profile_link', 191)->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('salons');
    }
}
