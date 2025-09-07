<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sms_campaigns', function (Blueprint $table) {
            $table->string('approval_status')->default('approved')->after('status'); // approved, pending, rejected
            $table->unsignedBigInteger('approved_by')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
            $table->boolean('uses_template')->default(false)->after('rejection_reason'); // true if sms_template_id was used
            $table->unsignedBigInteger('sms_template_id')->nullable()->after('uses_template');
            
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('sms_template_id')->references('id')->on('salon_sms_templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_campaigns', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['sms_template_id']);
            $table->dropColumn([
                'approval_status',
                'approved_by',
                'approved_at',
                'rejection_reason',
                'uses_template',
                'sms_template_id'
            ]);
        });
    }
};
