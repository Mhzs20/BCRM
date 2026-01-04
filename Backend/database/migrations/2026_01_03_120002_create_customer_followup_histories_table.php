<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customer_followup_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('salon_sms_templates')->onDelete('set null');
            $table->text('message');
            $table->timestamp('sent_at');
            $table->enum('type', ['manual', 'automatic'])->default('automatic');
            $table->timestamps();
            
            $table->index('salon_id');
            $table->index('customer_id');
            $table->index('sent_at');
            $table->index('type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_followup_histories');
    }
};
