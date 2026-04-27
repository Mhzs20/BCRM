<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\CommissionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job برای محاسبه و ثبت پورسانت نوبت انجام شده
 */
class ProcessAppointmentCommission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Appointment $appointment;

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
    public function handle(CommissionService $commissionService): void
    {
        try {
            Log::info('Processing commission for appointment', [
                'appointment_id' => $this->appointment->id,
                'staff_id' => $this->appointment->staff_id,
                'salon_id' => $this->appointment->salon_id,
            ]);

            $result = $commissionService->processAppointmentCommission($this->appointment);

            if ($result['success']) {
                Log::info('Commission processed successfully', [
                    'appointment_id' => $this->appointment->id,
                    'transactions_count' => count($result['transactions']),
                    'message' => $result['message'],
                ]);
            } else {
                Log::warning('Commission processing skipped or failed', [
                    'appointment_id' => $this->appointment->id,
                    'message' => $result['message'],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to process appointment commission', [
                'appointment_id' => $this->appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to allow retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessAppointmentCommission job failed permanently', [
            'appointment_id' => $this->appointment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
