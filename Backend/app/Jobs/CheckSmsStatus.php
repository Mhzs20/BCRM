<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment;
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

        Log::info('Finished CheckSmsStatus job.');
    }

    private function checkAppointmentSms(SmsService $smsService): void
    {
        $appointments = Appointment::where('reminder_sms_status', 'pending')
            ->orWhere('reminder_sms_status', 'processing')
            ->orWhere('satisfaction_sms_status', 'pending')
            ->orWhere('satisfaction_sms_status', 'processing')
            ->get();

        $messageIds = $appointments->pluck('reminder_sms_message_id')
            ->merge($appointments->pluck('satisfaction_sms_message_id'))
            ->filter()
            ->unique()
            ->toArray();

        if (empty($messageIds)) {
            return;
        }

        Log::info("Checking status for " . count($messageIds) . " appointment-related SMS messages.");
        $statuses = $smsService->checkSmsStatus($messageIds);

        if (empty($statuses)) {
            return;
        }

        foreach ($appointments as $appointment) {
            $this->updateAppointmentSmsStatus($appointment, 'reminder', $statuses, $smsService);
            $this->updateAppointmentSmsStatus($appointment, 'satisfaction', $statuses, $smsService);
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
        $pendingTransactions = SmsTransaction::where('status', 'pending')
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
}
