<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class CheckSmsStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(SmsService $smsService): void
    {
        // Fetch appointments that have pending SMS statuses
        $appointments = Appointment::whereIn('reminder_sms_status', ['pending'])
            ->orWhereIn('satisfaction_sms_status', ['pending'])
            ->get();

        $reminderMessageIds = $appointments->whereNotNull('reminder_sms_message_id')
                                           ->where('reminder_sms_status', 'pending')
                                           ->pluck('reminder_sms_message_id')
                                           ->toArray();

        $satisfactionMessageIds = $appointments->whereNotNull('satisfaction_sms_message_id')
                                               ->where('satisfaction_sms_status', 'pending')
                                               ->pluck('satisfaction_sms_message_id')
                                               ->toArray();

        $allMessageIds = array_unique(array_merge($reminderMessageIds, $satisfactionMessageIds));

        if (empty($allMessageIds)) {
            Log::info("No pending SMS messages to check status for.");
            return;
        }

        Log::info("Checking status for " . count($allMessageIds) . " SMS messages.");
        $kavenegarStatuses = $smsService->checkSmsStatus($allMessageIds);

        foreach ($appointments as $appointment) {
            $updated = false;

            // Check reminder SMS status
            if ($appointment->reminder_sms_status === 'pending' && $appointment->reminder_sms_message_id) {
                $messageId = $appointment->reminder_sms_message_id;
                if (isset($kavenegarStatuses[$messageId])) {
                    $newInternalStatus = $smsService->mapKavenegarStatusToInternal($kavenegarStatuses[$messageId]);
                    if ($newInternalStatus !== 'pending') {
                        $appointment->reminder_sms_status = $newInternalStatus;
                        $updated = true;
                        Log::info("Updated reminder SMS status for appointment {$appointment->id} to {$newInternalStatus}.");
                    }
                }
            }

            // Check satisfaction survey SMS status
            if ($appointment->satisfaction_sms_status === 'pending' && $appointment->satisfaction_sms_message_id) {
                $messageId = $appointment->satisfaction_sms_message_id;
                if (isset($kavenegarStatuses[$messageId])) {
                    $newInternalStatus = $smsService->mapKavenegarStatusToInternal($kavenegarStatuses[$messageId]);
                    if ($newInternalStatus !== 'pending') {
                        $appointment->satisfaction_sms_status = $newInternalStatus;
                        $updated = true;
                        Log::info("Updated satisfaction survey SMS status for appointment {$appointment->id} to {$newInternalStatus}.");
                    }
                }
            }

            if ($updated) {
                $appointment->save();
            }
        }
    }
}
