<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Services\SmsService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $smsService = app(SmsService::class);
        
        // Update type field based on sms_type and other conditions
        DB::statement("
            UPDATE sms_transactions 
            SET type = CASE
                WHEN type IS NOT NULL THEN type
                WHEN sms_type = 'purchase' THEN 'purchase'
                WHEN sms_type = 'gift' THEN 'gift'
                WHEN sms_type = 'manual_sms' THEN 'manual_send'
                WHEN sms_type IN ('appointment_confirmation', 'appointment_reminder', 'manual_reminder', 
                                  'appointment_cancellation', 'appointment_modification', 'satisfaction_survey',
                                  'birthday_greeting', 'service_specific_notes', 'wallet_notification') THEN 'send'
                WHEN description LIKE '%کسر اعتبار%' OR description LIKE '%deduction%' THEN 'deduction'
                WHEN amount < 0 THEN 'deduction'
                WHEN amount > 0 AND (description LIKE '%هدیه%' OR description LIKE '%gift%') THEN 'gift'
                ELSE 'send'
            END
            WHERE type IS NULL
        ");
        
        // Update sms_count for transactions that don't have it
        // For gift and purchase types, sms_count equals amount
        DB::statement("
            UPDATE sms_transactions 
            SET sms_count = CAST(ABS(amount) AS UNSIGNED)
            WHERE sms_count IS NULL 
            AND type IN ('gift', 'purchase')
            AND amount IS NOT NULL
        ");
        
        // Update sms_parts based on content for transactions without sms_parts
        $transactionsWithoutParts = DB::table('sms_transactions')
            ->whereNull('sms_parts')
            ->whereNotNull('content')
            ->select('id', 'content')
            ->get();
        
        foreach ($transactionsWithoutParts as $transaction) {
            $parts = $smsService->calculateSmsParts($transaction->content);
            DB::table('sms_transactions')
                ->where('id', $transaction->id)
                ->update([
                    'sms_parts' => $parts,
                    'sms_count' => DB::raw('COALESCE(sms_count, ' . $parts . ')')
                ]);
        }
        
        // Set default values for remaining null fields
        DB::statement("
            UPDATE sms_transactions 
            SET sms_parts = COALESCE(sms_parts, sms_count, 0),
                sms_count = COALESCE(sms_count, sms_parts, 0)
            WHERE sms_parts IS NULL OR sms_count IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We cannot reliably reverse this migration as we're filling in missing data
        // If needed, restore from backup
    }
};
