<?php

namespace App\Console\Commands;

use App\Jobs\SendRenewalReminderSms;
use App\Models\Appointment;
use App\Models\RenewalReminderLog;
use App\Models\RenewalReminderSetting;
use App\Models\ServiceRenewalSetting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SendRenewalReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'renewal:send-reminders {--dry-run : نمایش لیست بدون ارسال واقعی} {--force : ارسال بدون توجه به زمان تنظیم شده}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ارسال یادآوری ترمیم برای نوبت‌هایی که موعد ترمیم آن‌ها رسیده است';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');
        
        if ($isDryRun) {
            $this->info('🔍 حالت بررسی فعال - هیچ پیامکی ارسال نخواهد شد');
        }
        
        if ($force) {
            $this->info('⚡ حالت اجباری فعال - بدون توجه به زمان تنظیم شده');
        }

        $this->info('🚀 شروع بررسی یادآوری‌های ترمیم (سیستم جدید)...');

        $currentTime = Carbon::now()->format('H:i');
        $this->info("🕒 زمان فعلی: {$currentTime}");

        // دریافت تنظیمات فعال سرویس‌ها که سالن آن‌ها هم فعال است
        $query = ServiceRenewalSetting::where('is_active', true)
            ->whereHas('salon.renewalReminderSetting', function($q) {
                $q->where('is_active', true);
            });
        
        // Only check time if not forcing
        // Changed: Instead of exact minute match, check if current time >= reminder time
        // This ensures SMS is sent even if the exact minute was missed
        if (!$force) {
            $query->whereRaw("TIME(reminder_time) <= TIME(?)", [$currentTime . ':00']);
        }
        
        $activeServiceSettings = $query->with(['salon', 'service', 'template'])->get();

        if ($activeServiceSettings->isEmpty()) {
            $this->warn('❌ هیچ سرویسی تنظیمات یادآوری ترمیم فعال ندارد.');
            return;
        }

        $this->info("📋 تعداد {$activeServiceSettings->count()} سرویس با تنظیمات فعال یافت شد.");

        $totalSent = 0;
        $totalErrors = 0;

        foreach ($activeServiceSettings as $serviceSetting) {
            $salon = $serviceSetting->salon;
            $service = $serviceSetting->service;
            
            $this->info("\n🏪 بررسی سالن: {$salon->name} - سرویس: {$service->name}");

            try {
                // پیدا کردن نوبت‌هایی که نیاز به یادآوری دارند
                $appointments = $this->findAppointmentsNeedingReminder($serviceSetting);

                if ($appointments->isEmpty()) {
                    $this->warn("  ❌ هیچ نوبتی برای یادآوری یافت نشد.");
                    continue;
                }

                $this->info("  📋 تعداد {$appointments->count()} نوبت برای یادآوری یافت شد.");

                foreach ($appointments as $appointment) {
                    $customer = $appointment->customer;
                    
                    if (!$customer || !$customer->phone_number) {
                        $this->error("  ❌ مشتری نوبت {$appointment->id} شماره تلفن ندارد.");
                        $totalErrors++;
                        continue;
                    }

                    $existingLog = RenewalReminderLog::where('appointment_id', $appointment->id)
                        ->where('service_id', $service->id)
                        ->where(function($q) {
                            $q->where('status', 'sent')
                              ->orWhereDate('created_at', Carbon::today());
                        })
                        ->first();

                    if ($existingLog) {
                        $this->warn("  ⏭️ یادآوری برای نوبت {$appointment->id} - سرویس {$service->name} قبلاً ارسال شده.");
                        continue;
                    }

                    if ($isDryRun) {
                        $this->line("  🔍 [DRY RUN] یادآوری برای مشتری {$customer->name} (نوبت {$appointment->id} - سرویس {$service->name}) ارسال خواهد شد.");
                    } else {
                        try {
                            // ارسال Job برای ارسال یادآوری با سرویس مشخص
                            SendRenewalReminderSms::dispatch($appointment, $customer, $salon, $serviceSetting, $service);
                            
                            $this->info("  ✅ یادآوری برای مشتری {$customer->name} (نوبت {$appointment->id} - سرویس {$service->name}) در صف قرار گرفت.");
                            $totalSent++;
                        } catch (\Exception $e) {
                            $this->error("  ❌ خطا در ارسال یادآوری برای نوبت {$appointment->id}: " . $e->getMessage());
                            Log::error("Error dispatching renewal reminder job for appointment {$appointment->id}: " . $e->getMessage());
                            $totalErrors++;
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error("  ❌ خطا در پردازش سرویس {$service->name}: " . $e->getMessage());
                $totalErrors++;
            }
        }

        $this->info("\n📊 خلاصه عملیات:");
        if ($isDryRun) {
            $this->info("🔍 حالت بررسی: هیچ پیامکی ارسال نشد");
        } else {
            $this->info("✅ تعداد یادآوری‌های ارسال شده: {$totalSent}");
        }
        
        if ($totalErrors > 0) {
            $this->error("❌ تعداد خطاها: {$totalErrors}");
        }

        $this->info('🏁 پایان بررسی یادآوری‌های ترمیم.');
    }

    /**
     * پیدا کردن نوبت‌هایی که نیاز به یادآوری دارند
     */
    private function findAppointmentsNeedingReminder(ServiceRenewalSetting $serviceSetting)
    {
        // محاسبه تاریخ هدف: نوبت‌هایی که امروز موعد یادآوری آن‌ها رسیده
        $appointments = Appointment::where('salon_id', $serviceSetting->salon_id)
            ->where('status', 'completed')
            ->whereHas('services', function($query) use ($serviceSetting) {
                $query->where('services.id', $serviceSetting->service_id);
            })
            ->whereRaw('DATE_ADD(appointment_date, INTERVAL ? DAY) = DATE_ADD(CURDATE(), INTERVAL ? DAY)', [
                $serviceSetting->renewal_period_days,
                $serviceSetting->reminder_days_before
            ])
            ->with(['customer'])
            ->get();

        // فیلتر کردن آن‌هایی که قبلاً یادآوری ارسال شده
        $filteredAppointments = $appointments->filter(function($appointment) use ($serviceSetting) {
            $existingLog = RenewalReminderLog::where('appointment_id', $appointment->id)
                ->where('service_id', $serviceSetting->service_id)
                ->where('status', 'sent')
                ->exists();
            
            return !$existingLog;
        });

        return $filteredAppointments;
    }
}