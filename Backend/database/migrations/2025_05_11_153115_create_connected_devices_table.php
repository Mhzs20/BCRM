<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConnectedDevicesTable extends Migration
{
    public function up()
    {
        Schema::create('connected_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('device_identifier', 191)->unique();
            $table->string('ip_address')->nullable();
            $table->string('device_model')->nullable();
            $table->timestamp('last_login_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('connected_devices');
    }
}

// ... existing code ...
