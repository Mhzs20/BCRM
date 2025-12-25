<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exclusive_link_preparations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('salon_sms_templates')->onDelete('set null');
            $table->string('recipients_type')->comment('all_customers|selected_customers|phone_contacts');
            $table->json('recipients')->nullable()->comment('Array of recipients or phone numbers, depending on type');
            $table->integer('recipients_count')->default(0);
            $table->integer('estimated_parts')->default(0);
            $table->decimal('estimated_cost', 10, 2)->default(0);
            $table->json('sample')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exclusive_link_preparations');
    }
};