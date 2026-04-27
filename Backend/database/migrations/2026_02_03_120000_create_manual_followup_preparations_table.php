<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_followup_preparations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->json('customer_group_ids')->nullable();
            $table->json('service_ids')->nullable();
            $table->integer('days_since_last_visit');
            $table->json('customer_ids');
            $table->integer('customer_count');
            $table->integer('message_parts')->default(1);
            $table->integer('cost_per_message');
            $table->integer('total_cost');
            $table->text('sample_message')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_followup_preparations');
    }
};
