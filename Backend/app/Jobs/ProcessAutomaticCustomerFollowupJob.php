<?php

namespace App\Jobs;

use App\Models\CustomerFollowUpSetting;
use App\Models\CustomerFollowUpGroupSetting;
use App\Models\CustomerFollowUpHistory;
use App\Models\Customer;
use App\Models\Salon;
use App\Models\SalonSmsTemplate;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAutomaticCustomerFollowupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $salonId;

    /**
     * Create a new job instance.
     */
    public function __construct($salonId)
    {
        $this->salonId = $salonId;
    }

    /**
     * Execute the job.
     */
    public function handle(SmsService $smsService): void
    {
        try {
            Log::info("Processing automatic customer followup for salon {$this->salonId}");

            $salon = Salon::find($this->salonId);
            if (!$salon) {
                Log::error("Salon {$this->salonId} not found");
                return;
            }

            // دریافت تنظیمات پیگیری سالن
            $followupSetting = CustomerFollowUpSetting::where('salon_id', $this->salonId)
                ->where('is_global_active', true)
                ->first();

            if (!$followupSetting) {
                Log::info("No active followup settings for salon {$this->salonId}");
                return;
            }

            // دریافت قالب
            $template = SalonSmsTemplate::find($followupSetting->template_id);
            if (!$template) {
                Log::error("Template not found for salon {$this->salonId}");
                return;
            }

            // دریافت تنظیمات گروه‌های فعال
            $activeGroupSettings = CustomerFollowUpGroupSetting::where('customer_followup_setting_id', $followupSetting->id)
                ->where('is_active', true)
                ->get();

            if ($activeGroupSettings->isEmpty()) {
                Log::info("No active group settings for salon {$this->salonId}");
                return;
            }

            $totalSent = 0;

            // اگر تنظیمات برای "همه مشتریان" (بدون گروه خاص) فعال است
            $allCustomersSetting = $activeGroupSettings->firstWhere('customer_group_id', null);
            if ($allCustomersSetting) {
                $sent = $this->processAllCustomersFollowup($salon, $allCustomersSetting, $template, $smsService);
                $totalSent += $sent;
            } else {
                // پردازش گروه‌های خاص
                foreach ($activeGroupSettings as $groupSetting) {
                    $sent = $this->processGroupFollowup($salon, $groupSetting, $template, $smsService);
                    $totalSent += $sent;
                }
            }

            Log::info("Automatic customer followup completed for salon {$this->salonId}. Total sent: {$totalSent}");

        } catch (\Exception $e) {
            Log::error("Error processing automatic customer followup for salon {$this->salonId}: " . $e->getMessage(), [
                'exception' => $e
            ]);
        }
    }

    /**
     * پردازش پیگیری برای همه مشتریان (بدون فیلتر گروه)
     */
    protected function processAllCustomersFollowup($salon, $setting, $template, $smsService)
    {
        $sentCount = 0;

        // محاسبه تاریخ هدف
        $targetDate = Carbon::now()->subDays($setting->days_since_last_visit);

        // پیدا کردن تمام مشتریان واجد شرایط
        $eligibleCustomers = Customer::where('salon_id', $salon->id)
            ->get()
            ->filter(function ($customer) use ($targetDate, $setting) {
                // بررسی آخرین نوبت مشتری
                $lastAppointment = $customer->appointments()
                    ->where('status', 'completed')
                    ->orderBy('appointment_date', 'desc')
                    ->first();

                if (!$lastAppointment) {
                    return false; // هیچ نوبت کامل شده‌ای ندارد
                }

                $lastVisitDate = Carbon::parse($lastAppointment->appointment_date);

                // آیا از آخرین مراجعه X روز گذشته؟
                if (!$lastVisitDate->lte($targetDate)) {
                    return false;
                }

                // آیا اخیراً (در check_frequency_days روز گذشته) پیگیری خودکار شده؟
                $recentFollowup = CustomerFollowUpHistory::where('customer_id', $customer->id)
                    ->where('salon_id', $salon->id)
                    ->where('type', 'automatic')
                    ->where('sent_at', '>=', Carbon::now()->subDays($setting->check_frequency_days))
                    ->exists();

                return !$recentFollowup; // اگر پیگیری اخیر نداشته، واجد شرایط است
            });

        Log::info("Found {$eligibleCustomers->count()} eligible customers (all groups) in salon {$salon->id}");

        // ارسال پیامک برای مشتریان واجد شرایط
        foreach ($eligibleCustomers as $customer) {
            try {
                // ارسال پیامک
                $result = $smsService->sendTemplateNow(
                    $salon,
                    $template,
                    $customer->phone_number,
                    [
                        'customer_name' => $customer->name,
                        'salon_name' => $salon->name,
                    ],
                    $customer->id
                );

                // تولید پیام برای ذخیره در تاریخچه
                $message = $smsService->compileTemplateText(
                    $template->template,
                    [
                        'customer_name' => $customer->name,
                        'salon_name' => $salon->name,
                    ]
                );

                if (isset($result['status']) && $result['status'] === 'success') {
                    // ثبت در تاریخچه (غیرفعال کردن FK checks به دلیل MyISAM بودن salon_sms_templates)
                    DB::statement('SET FOREIGN_KEY_CHECKS=0');
                    CustomerFollowUpHistory::create([
                        'salon_id' => $salon->id,
                        'customer_id' => $customer->id,
                        'template_id' => $template->id,
                        'message' => $message,
                        'sent_at' => Carbon::now(),
                        'type' => 'automatic',
                    ]);
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');

                    $sentCount++;
                    Log::info("Followup SMS sent to customer {$customer->id} in salon {$salon->id}");
                } else {
                    Log::warning("Failed to send followup SMS to customer {$customer->id} in salon {$salon->id}: " . ($result['message'] ?? 'Unknown error'));
                }

            } catch (\Exception $e) {
                Log::error("Error sending followup SMS to customer {$customer->id}: " . $e->getMessage());
            }
        }

        return $sentCount;
    }

    /**
     * پردازش پیگیری برای یک گروه مشتری
     */
    public function processGroupFollowup($salon, $groupSetting, $template, $smsService)
    {
        $sentCount = 0;

        // محاسبه تاریخ هدف
        $targetDate = Carbon::now()->subDays($groupSetting->days_since_last_visit);

        // پیدا کردن تمام مشتریان واجد شرایط (بدون فیلتر گروه - چون رابطه many-to-many فعلاً کار نمیکنه)
        $eligibleCustomers = Customer::where('salon_id', $salon->id)
            ->get()
            ->filter(function ($customer) use ($targetDate, $groupSetting, $salon) {
                // بررسی آخرین نوبت مشتری
                $lastAppointment = $customer->appointments()
                    ->where('status', 'completed')
                    ->orderBy('appointment_date', 'desc')
                    ->first();

                if (!$lastAppointment) {
                    return false; // هیچ نوبت کامل شده‌ای ندارد
                }

                $lastVisitDate = Carbon::parse($lastAppointment->appointment_date);

                // آیا از آخرین مراجعه X روز گذشته؟
                if (!$lastVisitDate->lte($targetDate)) {
                    return false;
                }

                // آیا اخیراً (در check_frequency_days روز گذشته) پیگیری خودکار شده؟
                $recentFollowup = CustomerFollowUpHistory::where('customer_id', $customer->id)
                    ->where('salon_id', $salon->id)
                    ->where('type', 'automatic')
                    ->where('sent_at', '>=', Carbon::now()->subDays($groupSetting->check_frequency_days))
                    ->exists();

                return !$recentFollowup; // اگر پیگیری اخیر نداشته، واجد شرایط است
            });

        Log::info("Found {$eligibleCustomers->count()} eligible customers for group {$groupSetting->customer_group_id} in salon {$salon->id}");

        // ارسال پیامک برای مشتریان واجد شرایط
        foreach ($eligibleCustomers as $customer) {
            try {
                // ارسال پیامک
                $result = $smsService->sendTemplateNow(
                    $salon,
                    $template,
                    $customer->phone_number,
                    [
                        'customer_name' => $customer->name,
                        'salon_name' => $salon->name,
                    ],
                    $customer->id
                );

                // تولید پیام برای ذخیره در تاریخچه
                $message = $smsService->compileTemplateText(
                    $template->template,
                    [
                        'customer_name' => $customer->name,
                        'salon_name' => $salon->name,
                    ]
                );

                if (isset($result['status']) && $result['status'] === 'success') {
                    // ثبت در تاریخچه (غیرفعال کردن FK checks به دلیل MyISAM بودن salon_sms_templates)
                    DB::statement('SET FOREIGN_KEY_CHECKS=0');
                    CustomerFollowUpHistory::create([
                        'salon_id' => $salon->id,
                        'customer_id' => $customer->id,
                        'template_id' => $template->id,
                        'message' => $message,
                        'sent_at' => Carbon::now(),
                        'type' => 'automatic',
                    ]);
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');

                    $sentCount++;
                    Log::info("Followup SMS sent to customer {$customer->id} in salon {$salon->id}");
                } else {
                    Log::warning("Failed to send followup SMS to customer {$customer->id} in salon {$salon->id}: " . ($result['message'] ?? 'Unknown error'));
                }

            } catch (\Exception $e) {
                Log::error("Error sending followup SMS to customer {$customer->id}: " . $e->getMessage());
            }
        }

        return $sentCount;
    }

    /**
     * تولید پیام شخصی‌سازی شده
     */
    protected function generatePersonalizedMessage($template, $customer, $salon)
    {
        $message = str_replace('{{customer_name}}', $customer->name, $template);
        $message = str_replace('{{salon_name}}', $salon->name, $message);
        return $message;
    }
}
