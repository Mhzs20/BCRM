<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Salon;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAppointmentConfirmationSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The customer instance.
     *
     * @var \App\Models\Customer
     */
    public $customer;

    /**
     * The appointment instance.
     *
     * @var \App\Models\Appointment
     */
    public $appointment;

    /**
     * The salon instance.
     *
     * @var \App\Models\Salon
     */
    public $salon;

    /**
     * The template ID for SMS.
     *
     * @var int|null
     */
    public $templateId;

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\Customer  $customer
     * @param  \App\Models\Appointment  $appointment
     * @param  \App\Models\Salon  $salon
     * @param  int|null  $templateId
     * @return void
     */
    public function __construct(Customer $customer, Appointment $appointment, Salon $salon, ?int $templateId = null)
    {
        $this->customer = $customer;
        $this->appointment = $appointment;
        $this->salon = $salon;
        $this->templateId = $templateId;
    }

    /**
     * Execute the job.
     *
     * @param  \App\Services\SmsService  $smsService
     * @return void
     */
    public function handle(SmsService $smsService)
    {
        try {
            Log::info("Executing SendAppointmentConfirmationSms job for Appointment ID: {$this->appointment->id} with template ID: " . ($this->templateId ?? 'default'));
            
            // Reload appointment with services and staff to ensure relations are available
            $appointment = $this->appointment->load(['services', 'staff']);
            
            if ($this->templateId) {
                // Use template-based SMS sending
                $smsService->sendAppointmentConfirmationWithTemplate($this->customer, $appointment, $this->salon, $this->templateId);
            } else {
                // Use default SMS sending
                $smsService->sendAppointmentConfirmation($this->customer, $appointment, $this->salon);
            }
            
            Log::info("Successfully executed SendAppointmentConfirmationSms job for Appointment ID: {$this->appointment->id}");
        } catch (\Exception $e) {
            Log::error("Error in SendAppointmentConfirmationSms job for Appointment ID: {$this->appointment->id}. Error: " . $e->getMessage());
            // Optionally, re-throw the exception to let Laravel's queue worker handle the failure (e.g., move to failed_jobs table)
            throw $e;
        }
    }
}
