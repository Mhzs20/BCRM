<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customer_followup_service_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_followup_setting_id')
                ->constrained('customer_followup_settings', 'id', 'cf_service_setting_fk')
                ->onDelete('cascade');
            $table->foreignId('service_id')
                ->constrained('services', 'id', 'cf_service_fk')
                ->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['customer_followup_setting_id', 'service_id'], 'cf_setting_service_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_followup_service_settings');
    }
};
