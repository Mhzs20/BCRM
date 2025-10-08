<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\RenewalReminderLog;
use App\Models\RenewalReminderSetting;
use App\Models\ServiceRenewalSetting;
use App\Models\Service;
use App\Models\Salon;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;

class SendRenewalReminderSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $appointment;
    protected $customer;
    protected $salon;
    protected $serviceSetting;
    protected $service;

    /**
     * Create a new job instance.
     */
    public function __construct(Appointment $appointment, Customer $customer, Salon $salon, ServiceRenewalSetting $serviceSetting, Service $service)
    {
        $this->appointment = $appointment;
        $this->customer = $customer;
        $this->salon = $salon;
        $this->serviceSetting = $serviceSetting;
        $this->service = $service;
    }

    /**
     * Execute the job.
     */
    public function handle(SmsService $smsService): void
    {
        try {
            // بررسی اینکه آیا قبلاً یادآوری ارسال شده یا نه
            $existingLog = RenewalReminderLog::where('appointment_id', $this->appointment->id)
                ->where('service_id', $this->service->id)
                ->where('status', 'sent')
                ->first();

            if ($existingLog) {
                Log::info("Renewal reminder already sent for appointment {$this->appointment->id} - service {$this->service->id}");
                return;
            }

            // دریافت قالب از تنظیمات سرویس
            if (!$this->serviceSetting->template) {
                Log::error("No template found for service {$this->service->id} in salon {$this->salon->id}");
                return;
            }

            $template = $this->serviceSetting->template;

            // تهیه داده‌ها برای جایگزینی در قالب
            $appointmentDate = Jalalian::fromCarbon(Carbon::parse($this->appointment->appointment_date))->format('Y/m/d');

            $templateData = [
                'customer_name' => $this->customer->name,
                'salon_name' => $this->salon->name,
                'service_name' => $this->service->name, // فقط سرویس مربوطه
                'appointment_date' => $appointmentDate,
                'appointment_time' => $this->appointment->start_time,
                'salon_phone' => $this->salon->mobile ?? $this->salon->phone,
            ];

            // جایگزینی متغیرها در قالب
            $message = $this->replaceTemplateVariables($template->template, $templateData);

            // ایجاد لاگ قبل از ارسال
            $reminderLog = RenewalReminderLog::create([
                'salon_id' => $this->salon->id,
                'appointment_id' => $this->appointment->id,
                'customer_id' => $this->customer->id,
                'service_id' => $this->service->id,
                'message_content' => $message,
                'status' => 'pending'
            ]);

            // ارسال پیامک
            $result = $smsService->sendSms($this->customer->phone_number, $message);

            // به‌روزرسانی وضعیت بر اساس نتیجه ارسال
            if (isset($result['status']) && $result['status'] === 'success') {
                $reminderLog->update([
                    'status' => 'sent',
                    'sms_message_id' => $result['message_id'] ?? null,
                    'sent_at' => now()
                ]);

                Log::info("Renewal reminder sent successfully for appointment {$this->appointment->id}");
            } else {
                $reminderLog->update([
                    'status' => 'failed',
                    'error_message' => $result['message'] ?? 'خطای نامشخص در ارسال پیامک'
                ]);

                Log::error("Failed to send renewal reminder for appointment {$this->appointment->id}: " . ($result['message'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            Log::error("Error in SendRenewalReminderSms job for appointment {$this->appointment->id}: " . $e->getMessage());
            
            // در صورت بروز خطا، لاگ را به‌روزرسانی کن
            if (isset($reminderLog)) {
                $reminderLog->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * جایگزینی متغیرها در قالب پیامک
     */
    private function replaceTemplateVariables(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        return $template;
    }
}