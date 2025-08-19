<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSatisfactionSurveySms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $appointment;

    /**
     * Create a new job instance.
     */
    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    /**
     * Execute the job.
     */
    public function handle(SmsService $smsService): void
    {
        $appointment = $this->appointment->fresh();
        if (!$appointment || !$appointment->customer) {
            Log::info("Skipping satisfaction survey SMS for appointment {$this->appointment->id}: not found or no customer.");
            return;
        }

        $customer = $appointment->customer;
        $salonOwner = $this->salon->user; // Assuming Salon has a user relationship

        if (!$salonOwner) {
            Log::warning("Salon owner not found for salon ID {$this->salon->id}. Cannot send satisfaction survey SMS.");
            return;
        }

        $smsResult = $smsService->sendSatisfactionSurvey($customer, $appointment, $this->salon);

        if ($smsResult['status'] === 'success') {
            Log::info("Successfully sent satisfaction survey SMS for appointment {$appointment->id}.");
        } else {
            Log::error("Failed to send satisfaction survey SMS for appointment {$appointment->id}: " . $smsResult['message']);
            $appointment->update(['satisfaction_sms_status' => 'failed']);
        }

    }
}
