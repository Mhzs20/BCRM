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

        $smsService->sendSatisfactionSurvey($appointment->customer, $appointment, $appointment->salon);
    }
}
