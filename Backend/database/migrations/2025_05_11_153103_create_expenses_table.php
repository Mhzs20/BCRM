<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpensesTable extends Migration
{
    public function up()
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->date('date');
            $table->text('description');
            $table->decimal('amount', 10, 2);
            $table->string('category')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('expenses');
    }
}
