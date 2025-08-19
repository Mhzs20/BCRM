<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment;
use App\Models\Salon;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminderSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $appointment;
    protected $salon;

    /**
     * Create a new job instance.
     */
    public function __construct(Appointment $appointment, Salon $salon)
    {
        $this->appointment = $appointment;
        $this->salon = $salon;
    }

    /**
     * Execute the job.
     */
    public function handle(SmsService $smsService): void
    {
        // Ensure the appointment and customer exist and reminder is enabled
        $appointment = $this->appointment->fresh(); // Get fresh instance
        if (!$appointment || !$appointment->customer || !$appointment->send_reminder_sms) {
            Log::info("Skipping reminder SMS for appointment {$this->appointment->id}: not found, no customer, or reminder disabled.");
            return;
        }

        // Check if SMS has a final status already
        if (!in_array($appointment->reminder_sms_status, ['not_sent', 'processing', null])) {
            Log::info("Reminder SMS for appointment {$appointment->id} already has a final status '{$appointment->reminder_sms_status}'. Skipping.");
            return;
        }

        $customer = $appointment->customer;
        $salonOwner = $this->salon->user; // Assuming Salon has a user relationship

        if (!$salonOwner) {
            Log::warning("Salon owner not found for salon ID {$this->salon->id}. Cannot send reminder SMS.");
            return;
        }

        $smsResult = $smsService->sendAppointmentReminder($customer, $appointment, $this->salon);

        if ($smsResult['status'] === 'success') {
            Log::info("Successfully sent reminder SMS for appointment {$appointment->id}.");
        } else {
            Log::error("Failed to send reminder SMS for appointment {$appointment->id}: " . $smsResult['message']);
            $appointment->update(['reminder_sms_status' => 'failed']);
        }
    }
}
