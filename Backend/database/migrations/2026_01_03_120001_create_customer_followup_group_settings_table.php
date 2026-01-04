<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customer_followup_group_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_followup_setting_id')
                ->constrained('customer_followup_settings', 'id', 'cf_group_setting_fk')
                ->onDelete('cascade');
            $table->foreignId('customer_group_id')
                ->constrained('customer_groups', 'id', 'cf_group_customer_fk')
                ->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->integer('days_since_last_visit')->default(15); // تعداد روزهایی که از آخرین مراجعه گذشته
            $table->integer('check_frequency_days')->default(7); // هر چند روز یکبار چک کنه
            $table->timestamps();
            
            $table->unique(['customer_followup_setting_id', 'customer_group_id'], 'cf_setting_group_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_followup_group_settings');
    }
};
