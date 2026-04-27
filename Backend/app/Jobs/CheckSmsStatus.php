<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment;
use App\Models\RenewalReminderLog;
use App\Models\SatisfactionSurveyLog;
use App\Models\SmsCampaignMessage;
use App\Models\SmsTransaction;
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
        Log::info('Running CheckSmsStatus job.');

        // --- Check Appointment SMS ---
        $this->checkAppointmentSms($smsService);

        // --- Check Manual SMS Transactions ---
        $this->checkManualSmsTransactions($smsService);

        // --- Check SMS Campaign Messages ---
        $this->checkSmsCampaignMessages($smsService);

        // --- Check Renewal Reminder Logs ---
        $this->checkRenewalReminderLogs($smsService);

        // --- Check Satisfaction Survey Logs ---
        $this->checkSatisfactionSurveyLogs($smsService);

        Log::info('Finished CheckSmsStatus job.');
    }

    private function checkAppointmentSms(SmsService $smsService): void
    {
        $appointments = Appointment::where('reminder_sms_status', 'pending')
            ->orWhere('reminder_sms_status', 'processing')
            ->orWhere('satisfaction_sms_status', 'pending')
            ->orWhere('satisfaction_sms_status', 'processing')
            ->orWhere('confirmation_sms_status', 'pending')
            ->orWhere('confirmation_sms_status', 'processing')
            ->get();

        $messageIds = $appointments->pluck('reminder_sms_message_id')
            ->merge($appointments->pluck('satisfaction_sms_message_id'))
            ->merge($appointments->pluck('confirmation_sms_message_id'))
            ->filter()
            ->unique()
            ->toArray();

        if (empty($messageIds)) {
            return;
        }

        Log::info("Checking status for " . count($messageIds) . " appointment-related SMS messages.");
        
        // Process in chunks of 50 to avoid timeout
        $chunks = array_chunk($messageIds, 50);
        $allStatuses = [];
        
        foreach ($chunks as $index => $chunk) {
            Log::info("Processing chunk " . ($index + 1) . "/" . count($chunks) . " with " . count($chunk) . " messages.");
            $statuses = $smsService->checkSmsStatus($chunk);
            if (!empty($statuses)) {
                $allStatuses = array_merge($allStatuses, $statuses);
            }
            // Small delay between chunks to avoid rate limiting
            if ($index < count($chunks) - 1) {
                usleep(500000); // 0.5 second delay
            }
        }

        if (empty($allStatuses)) {
            return;
        }

        $statuses = $allStatuses;

        foreach ($appointments as $appointment) {
            $this->updateAppointmentSmsStatus($appointment, 'reminder', $statuses, $smsService);
            $this->updateAppointmentSmsStatus($appointment, 'satisfaction', $statuses, $smsService);
            $this->updateAppointmentSmsStatus($appointment, 'confirmation', $statuses, $smsService);
        }
    }

    private function updateAppointmentSmsStatus(Appointment $appointment, string $type, array $statuses, SmsService $smsService): void
    {
        $statusField = "{$type}_sms_status";
        $messageIdField = "{$type}_sms_message_id";

        if (in_array($appointment->$statusField, ['pending', 'processing']) && $appointment->$messageIdField) {
            $messageId = $appointment->$messageIdField;
            if (isset($statuses[$messageId])) {
                $newStatus = $smsService->mapKavenegarStatusToInternal($statuses[$messageId]);
                if (!in_array($newStatus, ['pending', 'processing'])) {
                    $appointment->$statusField = $newStatus;
                    $appointment->save();
                    Log::info("Updated {$type} SMS status for appointment {$appointment->id} to {$newStatus}.");
                }
            }
        }
    }

    private function checkManualSmsTransactions(SmsService $smsService): void
    {
        $pendingTransactions = SmsTransaction::whereIn('status', ['pending', 'processing'])
            ->whereNotNull('external_response')
            ->limit(100) // Process in chunks
            ->get();

        if ($pendingTransactions->isEmpty()) {
            return;
        }

        $transactionsByMessageId = [];
        foreach ($pendingTransactions as $transaction) {
            $response = json_decode($transaction->external_response, true);
            
            // Handle array of entries (standard Kavenegar response)
            if (is_array($response) && isset($response[0]['messageid'])) {
                $transactionsByMessageId[$response[0]['messageid']] = $transaction;
            } 
            // Handle single object (fallback)
            elseif (is_array($response) && isset($response['messageid'])) {
                $transactionsByMessageId[$response['messageid']] = $transaction;
            }
        }

        $messageIds = array_keys($transactionsByMessageId);
        if (empty($messageIds)) {
            return;
        }

        Log::info("Checking status for " . count($messageIds) . " manual SMS transactions.");
        $statuses = $smsService->checkSmsStatus($messageIds);

        if (empty($statuses)) {
            Log::warning('SMS Status Check: Failed to retrieve statuses for manual transactions.');
            return;
        }

        $updatedCount = 0;
        foreach ($statuses as $messageId => $kavenegarStatus) {
            if (isset($transactionsByMessageId[$messageId])) {
                $transaction = $transactionsByMessageId[$messageId];
                $newStatus = $smsService->mapKavenegarStatusToInternal($kavenegarStatus);

                if ($transaction->status !== $newStatus) {
                    $transaction->status = $newStatus;
                    $transaction->save();
                    $updatedCount++;
                }
            }
        }
        Log::info("SMS Status Check: Updated {$updatedCount} manual SMS transactions.");
    }

    private function checkSmsCampaignMessages(SmsService $smsService): void
    {
        $pendingMessages = SmsCampaignMessage::whereIn('status', ['pending', 'processing'])
            ->whereNotNull('message_id')
            ->limit(200) // Process in chunks
            ->get();

        if ($pendingMessages->isEmpty()) {
            return;
        }

        $messageIds = $pendingMessages->pluck('message_id')->filter()->unique()->toArray();
        if (empty($messageIds)) {
            return;
        }

        Log::info("Checking status for " . count($messageIds) . " SMS campaign messages.");
        
        // Process in chunks of 50 to avoid timeout
        $chunks = array_chunk($messageIds, 50);
        $allStatuses = [];
        
        foreach ($chunks as $index => $chunk) {
            Log::info("Processing chunk " . ($index + 1) . "/" . count($chunks) . " with " . count($chunk) . " messages.");
            $statuses = $smsService->checkSmsStatus($chunk);
            if (!empty($statuses)) {
                $allStatuses = array_merge($allStatuses, $statuses);
            }
            // Small delay between chunks to avoid rate limiting
            if ($index < count($chunks) - 1) {
                usleep(500000); // 0.5 second delay
            }
        }

        if (empty($allStatuses)) {
            Log::warning('SMS Status Check: Failed to retrieve statuses for campaign messages.');
            return;
        }

        $updatedCount = 0;
        foreach ($pendingMessages as $message) {
            if (isset($allStatuses[$message->message_id])) {
                $kavenegarStatus = $allStatuses[$message->message_id];
                $newStatus = $smsService->mapKavenegarStatusToInternal($kavenegarStatus);

                // Only update if status actually changed and is no longer pending
                if ($message->status !== $newStatus && !in_array($newStatus, ['pending', 'processing'])) {
                    $message->status = $newStatus;
                    $message->save();
                    $updatedCount++;
                    Log::info("Updated SMS campaign message #{$message->id} status to {$newStatus}.");
                }
            }
        }
        
        Log::info("SMS Status Check: Updated {$updatedCount} SMS campaign messages.");
    }

    private function checkRenewalReminderLogs(SmsService $smsService): void
    {
        $pendingLogs = RenewalReminderLog::whereIn('status', ['pending', 'processing'])
            ->whereNotNull('sms_message_id')
            ->limit(100)
            ->get();

        if ($pendingLogs->isEmpty()) {
            return;
        }

        $messageIds = $pendingLogs->pluck('sms_message_id')->filter()->unique()->toArray();
        if (empty($messageIds)) {
            return;
        }

        Log::info("Checking status for " . count($messageIds) . " renewal reminder logs.");
        $statuses = $smsService->checkSmsStatus($messageIds);

        if (empty($statuses)) {
            Log::warning('SMS Status Check: Failed to retrieve statuses for renewal reminder logs.');
            return;
        }

        $updatedCount = 0;
        foreach ($pendingLogs as $log) {
            if (isset($statuses[$log->sms_message_id])) {
                $kavenegarStatus = $statuses[$log->sms_message_id];
                $newStatus = $smsService->mapKavenegarStatusToInternal($kavenegarStatus);

                if ($log->status !== $newStatus && !in_array($newStatus, ['pending', 'processing'])) {
                    $log->status = $newStatus;
                    $log->save();
                    $updatedCount++;
                    Log::info("Updated renewal reminder log #{$log->id} status to {$newStatus}.");
                }
            }
        }

        Log::info("SMS Status Check: Updated {$updatedCount} renewal reminder logs.");
    }

    private function checkSatisfactionSurveyLogs(SmsService $smsService): void
    {
        $pendingLogs = SatisfactionSurveyLog::whereIn('status', ['pending', 'processing'])
            ->whereNotNull('message_id')
            ->limit(100)
            ->get();

        if ($pendingLogs->isEmpty()) {
            return;
        }

        $messageIds = $pendingLogs->pluck('message_id')->filter()->unique()->toArray();
        if (empty($messageIds)) {
            return;
        }

        Log::info("Checking status for " . count($messageIds) . " satisfaction survey logs.");
        $statuses = $smsService->checkSmsStatus($messageIds);

        if (empty($statuses)) {
            Log::warning('SMS Status Check: Failed to retrieve statuses for satisfaction survey logs.');
            return;
        }

        $updatedCount = 0;
        foreach ($pendingLogs as $log) {
            if (isset($statuses[$log->message_id])) {
                $kavenegarStatus = $statuses[$log->message_id];
                $newStatus = $smsService->mapKavenegarStatusToInternal($kavenegarStatus);

                if ($log->status !== $newStatus && !in_array($newStatus, ['pending', 'processing'])) {
                    $log->status = $newStatus;
                    $log->save();
                    $updatedCount++;
                    Log::info("Updated satisfaction survey log #{$log->id} status to {$newStatus}.");
                }
            }
        }

        Log::info("SMS Status Check: Updated {$updatedCount} satisfaction survey logs.");
    }
}
