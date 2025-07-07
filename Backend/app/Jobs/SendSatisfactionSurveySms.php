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

class SendSatisfactionSurveySms implements ShouldQueue
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
        // Ensure the appointment and customer exist and survey is enabled
        $appointment = $this->appointment->fresh(); // Get fresh instance
        if (!$appointment || !$appointment->customer || !$appointment->send_satisfaction_sms) {
            Log::info("Skipping satisfaction survey SMS for appointment {$this->appointment->id}: not found, no customer, or survey disabled.");
            return;
        }

        // Check if SMS already sent or is pending
        if ($appointment->satisfaction_sms_status !== 'not_sent') {
            Log::info("Satisfaction survey SMS for appointment {$appointment->id} already has status '{$appointment->satisfaction_sms_status}'. Skipping.");
            return;
        }

        $customer = $appointment->customer;
        $salonOwner = $this->salon->user; // Assuming Salon has a user relationship

        if (!$salonOwner) {
            Log::warning("Salon owner not found for salon ID {$this->salon->id}. Cannot send satisfaction survey SMS.");
            return;
        }

        $sent = $smsService->sendSatisfactionSurvey($customer, $appointment, $this->salon);

        if ($sent) {
            Log::info("Satisfaction survey SMS dispatched for appointment {$appointment->id}.");
        } else {
            Log::error("Failed to dispatch satisfaction survey SMS for appointment {$appointment->id}.");
            // Optionally, re-queue the job or log a more specific error
        }
    }
}
