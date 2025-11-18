<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Appointment;

class CancelPastAppointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:cancel-past';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel past appointments that are still active and their time has passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now('Asia/Tehran');
        $todayDate = $now->format('Y-m-d');
        $currentTime = $now->format('H:i');
        
        // Get appointments that should be canceled
        $appointments = Appointment::whereIn('status', ['confirmed', 'pending_confirmation'])
            ->where(function ($query) use ($todayDate, $currentTime) {
                // Past dates
                $query->where('appointment_date', '<', $todayDate)
                    // Or today but time has passed
                    ->orWhere(function ($subQuery) use ($todayDate, $currentTime) {
                        $subQuery->where('appointment_date', '=', $todayDate)
                            ->where('start_time', '<', $currentTime);
                    });
            })
            ->get();

        $canceledCount = 0;
        
        foreach ($appointments as $appointment) {
            try {
                // Update appointment status to waiting
                $appointment->update(['status' => 'waiting']);
                $canceledCount++;
                
                $customerName = $appointment->customer ? $appointment->customer->name : 'نامشخص';
                $this->line("نوبت ID {$appointment->id} برای مشتری {$customerName} در تاریخ {$appointment->appointment_date} ساعت {$appointment->start_time} به وضعیت در انتظار تغییر یافت.");
            } catch (\Exception $e) {
                $this->error("خطا در تغییر وضعیت نوبت ID {$appointment->id}: " . $e->getMessage());
            }
        }

        $this->info("تعداد {$canceledCount} نوبت گذشته به وضعیت در انتظار تغییر یافت.");
        
        return 0;
    }
}
