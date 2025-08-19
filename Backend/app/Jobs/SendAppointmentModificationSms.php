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

class SendAppointmentModificationSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $customer;
    public $appointment;
    public $salon;

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\Customer  $customer
     * @param  \App\Models\Appointment  $appointment
     * @param  \App\Models\Salon  $salon
     * @return void
     */
    public function __construct(Customer $customer, Appointment $appointment, Salon $salon)
    {
        $this->customer = $customer;
        $this->appointment = $appointment;
        $this->salon = $salon;
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
            Log::info("Executing SendAppointmentModificationSms job for Appointment ID: {$this->appointment->id}");
            $smsService->sendAppointmentModification($this->customer, $this->appointment, $this->salon);
            Log::info("Successfully executed SendAppointmentModificationSms job for Appointment ID: {$this->appointment->id}");
        } catch (\Exception $e) {
            Log::error("Error in SendAppointmentModificationSms job for Appointment ID: {$this->appointment->id}. Error: " . $e->getMessage());
            throw $e;
        }
    }
}
