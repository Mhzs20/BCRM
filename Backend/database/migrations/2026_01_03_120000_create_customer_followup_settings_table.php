<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customer_followup_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('salon_sms_templates')->onDelete('set null');
            $table->boolean('is_global_active')->default(false);
            $table->timestamps();
            
            $table->unique('salon_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_followup_settings');
    }
};
