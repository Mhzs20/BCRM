<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Salon;
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

        // Check if SMS has a final status already
        if (!in_array($appointment->satisfaction_sms_status, ['not_sent', 'processing', null])) {
            Log::info("Satisfaction survey SMS for appointment {$appointment->id} already has a final status '{$appointment->satisfaction_sms_status}'. Skipping.");
            return;
        }

        $customer = $appointment->customer;
        $salonOwner = $this->salon->user; // Assuming Salon has a user relationship

        if (!$salonOwner) {
            Log::warning("Salon owner not found for salon ID {$this->salon->id}. Cannot send satisfaction survey SMS.");
            return;
        }

        // Only mark as processing if not already marked
        if ($appointment->satisfaction_sms_status === 'not_sent') {
            $appointment->satisfaction_sms_status = 'processing';
            $appointment->save();
        }

        $smsService->sendSatisfactionSurvey($customer, $appointment, $this->salon);
        // The SmsService now handles updating the appointment's SMS status.
        Log::info("Satisfaction survey SMS sending process initiated for appointment {$appointment->id}. Status updates handled by SmsService.");
    }
}
