<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Fix existing customer_feedback records:
     * 1. Set is_submitted = true and submitted_at for all records with a rating
     * 2. Populate staff_id and service_id from their appointment data
     * 3. Set survey_sms_sent_at on appointments that have satisfaction_survey_logs
     */
    public function up(): void
    {
        // Fix 1: Mark all existing feedbacks as submitted (they were all submitted via form)
        $updated = DB::table('customer_feedback')
            ->where('is_submitted', false)
            ->whereNotNull('rating')
            ->update([
                'is_submitted' => true,
                'submitted_at' => DB::raw('COALESCE(updated_at, created_at)'),
            ]);
        Log::info("Fixed {$updated} customer_feedback records: set is_submitted=true");

        // Fix 2: Populate staff_id from appointment where missing
        $feedbacksWithoutStaff = DB::table('customer_feedback')
            ->whereNull('customer_feedback.staff_id')
            ->join('appointments', 'customer_feedback.appointment_id', '=', 'appointments.id')
            ->whereNotNull('appointments.staff_id')
            ->select('customer_feedback.id', 'appointments.staff_id')
            ->get();

        foreach ($feedbacksWithoutStaff as $fb) {
            DB::table('customer_feedback')
                ->where('id', $fb->id)
                ->update(['staff_id' => $fb->staff_id]);
        }
        Log::info("Fixed " . $feedbacksWithoutStaff->count() . " customer_feedback records: populated staff_id");

        // Fix 3: Populate service_id from appointment's first service where missing
        $feedbacksWithoutService = DB::table('customer_feedback')
            ->whereNull('service_id')
            ->join('appointments', 'customer_feedback.appointment_id', '=', 'appointments.id')
            ->select('customer_feedback.id', 'customer_feedback.appointment_id')
            ->get();

        foreach ($feedbacksWithoutService as $fb) {
            $firstService = DB::table('appointment_service')
                ->where('appointment_id', $fb->appointment_id)
                ->first();
            if ($firstService) {
                DB::table('customer_feedback')
                    ->where('id', $fb->id)
                    ->update(['service_id' => $firstService->service_id]);
            }
        }
        Log::info("Fixed " . $feedbacksWithoutService->count() . " customer_feedback records: populated service_id");

        // Fix 4: Set survey_sms_sent_at from satisfaction_survey_logs
        if (\Illuminate\Support\Facades\Schema::hasTable('satisfaction_survey_logs')) {
            $logsWithSent = DB::table('satisfaction_survey_logs')
                ->whereIn('status', ['sent', 'delivered'])
                ->whereNotNull('sent_at')
                ->select('appointment_id', DB::raw('MIN(sent_at) as first_sent_at'))
                ->groupBy('appointment_id')
                ->get();

            foreach ($logsWithSent as $log) {
                DB::table('appointments')
                    ->where('id', $log->appointment_id)
                    ->whereNull('survey_sms_sent_at')
                    ->update(['survey_sms_sent_at' => $log->first_sent_at]);
            }
            Log::info("Fixed " . $logsWithSent->count() . " appointments: set survey_sms_sent_at from logs");
        }

        // Fix 5: Set survey_sms_sent_at from satisfaction_sms_status for manual sends
        $manualSends = DB::table('appointments')
            ->whereNull('survey_sms_sent_at')
            ->whereIn('satisfaction_sms_status', ['sent', 'delivered'])
            ->update(['survey_sms_sent_at' => DB::raw('updated_at')]);
        Log::info("Fixed {$manualSends} appointments: set survey_sms_sent_at from satisfaction_sms_status");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a data fix migration, reverting would lose data accuracy
        // Intentionally left empty
    }
};
