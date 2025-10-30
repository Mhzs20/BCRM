<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('content');
            $table->string('type')->default('birthday');
            $table->unsignedBigInteger('salon_id')->nullable();
            $table->boolean('is_global')->default(false);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('templates');
    }
};
