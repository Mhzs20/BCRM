<?php

namespace App\Services;

use App\Models\User;
use App\Models\SmsTransaction;
use App\Models\UserSmsBalance;
use App\Models\Salon;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\SalonSmsTemplate;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class SmsService
{
    protected $apiKey;
    protected $senderNumber;
    protected $apiUrl;
    protected $smsCharacterLimit;

    public function __construct()
    {
        $this->apiKey = env('SMS_API_KEY', 'YOUR_DEFAULT_KEY_IF_NOT_SET');
        $this->senderNumber = env('SMS_SENDER_NUMBER', 'YOUR_DEFAULT_SENDER_IF_NOT_SET');
        $this->apiUrl = env('SMS_API_URL', 'YOUR_DEFAULT_API_URL_IF_NOT_SET');
        $smsCharacterLimitSetting = Setting::where('key', 'sms_character_limit')->first();
        $this->smsCharacterLimit = $smsCharacterLimitSetting ? (int)$smsCharacterLimitSetting->value : 70;


        if (env('APP_ENV') !== 'testing' && (!$this->apiKey || !$this->senderNumber || !$this->apiUrl || $this->apiKey === 'YOUR_DEFAULT_KEY_IF_NOT_SET')) {
            Log::warning('SMS Service is not configured properly in .env file. SMS sending will be simulated.');
        }
    }

    /**
     *
     * @param Salon $salon سالنی که تنظیمات پیامک از آن خوانده می‌شود
     * @param string $eventType نوع رویداد (مثلا 'appointment_confirmation')
     * @param string $receptor شماره گیرنده
     * @param User $salonOwner کاربری که هزینه پیامک از او کسر می‌شود
     * @param array $dataForTemplate داده‌هایی برای جایگزینی در قالب (مثلا ['customer_name' => 'علی'])
     * @param int|null $customerId شناسه مشتری مرتبط
     * @param int|null $appointmentId شناسه نوبت مرتبط
     * @return bool True on success/simulation or if not active, false on failure
     */
    private function sendMessageUsingTemplate(
        Salon $salon,
        string $eventType,
        string $receptor,
        User $salonOwner,
        array $dataForTemplate,
        ?int $customerId = null,
        ?int $appointmentId = null
    ): bool {
        $smsTemplate = $salon->getSmsTemplate($eventType); // متدی که در مدل Salon تعریف کردیم

        // اگر قالب وجود ندارد یا برای این نوع رویداد غیرفعال است، پیامک ارسال نشود
        if (!$smsTemplate || !$smsTemplate->is_active) {
            Log::info("SMS event '{$eventType}' is not active or template not found for Salon ID {$salon->id}. SMS to {$receptor} not sent.");
            // بسته به نیاز، می‌توانید true برگردانید تا عملیات اصلی (مثلا ثبت نوبت) متوقف نشود
            return true; // یا false اگر می‌خواهید عدم ارسال به عنوان خطا در نظر گرفته شود
        }

        $templateText = $smsTemplate->template ?: $this->getDefaultTextForEventType($eventType, $dataForTemplate);
        $message = $this->compileTemplate($templateText, $dataForTemplate);

        if (empty(trim($message))) {
            Log::warning("Compiled message for event '{$eventType}' for Salon ID {$salon->id} is empty. SMS to {$receptor} not sent.");
            return true; // یا false
        }

        // بررسی موجودی پیامک کاربر
        $userSmsBalance = UserSmsBalance::firstOrCreate(
            ['user_id' => $salonOwner->id],
            ['balance' => 0] // اگر موجودی نداشت، با صفر ایجاد می‌شود
        );

        if ($userSmsBalance->balance <= 0) {
            Log::warning("User ID {$salonOwner->id} (Salon: {$salon->id}) has insufficient SMS balance to send '{$eventType}' to {$receptor}. Balance: {$userSmsBalance->balance}");
            // ارسال نوتیفیکیشن به مدیر سالن برای اتمام موجودی
            // event(new UserSmsBalanceLowEvent($salonOwner));
            return false; // مهم: عدم ارسال به دلیل نبود موجودی باید خطا در نظر گرفته شود
        }

        // ارسال واقعی پیامک
        try {
            // ** شبیه‌سازی ارسال برای محیط توسعه **
            if (env('APP_ENV') !== 'production' || !$this->apiKey || $this->apiKey === 'YOUR_DEFAULT_KEY_IF_NOT_SET') {
                Log::info("[SMS SIMULATION] To: {$receptor} | SalonID: {$salon->id} | Type: {$eventType} | Message: {$message}");
                $status = 'sent_simulated';
                $externalResponse = 'Simulation successful in development.';
            } else {
                // ** کد ارسال واقعی با پنل پیامکی شما در اینجا قرار می‌گیرد **
                // مثال با HTTP (باید با مستندات پنل خود تطبیق دهید)
                /*
                $response = Http::withHeaders([
                    // هدرهای لازم برای پنل شما
                ])->post($this->apiUrl, [
                    'apikey' => $this->apiKey,
                    'receptor' => $receptor,
                    'sender' => $this->senderNumber,
                    'message' => $message,
                    // ... سایر پارامترهای لازم
                ]);

                if ($response->successful() && $this->isSendSuccessful($response->json())) { // isSendSuccessful متد کمکی شما برای بررسی پاسخ پنل
                    $status = 'sent';
                    $externalResponse = $response->body();
                } else {
                    Log::error("Failed to send '{$eventType}' SMS to {$receptor} for Salon ID {$salon->id}. API Response: " . $response->body());
                    $this->logTransaction($salonOwner->id, $receptor, $message, 'failed', $eventType, $salon->id, $customerId, $appointmentId, $response->body());
                    return false;
                }
                */
                // برای تست، این بخش را فعال کنید و بخش شبیه‌سازی را غیرفعال
                Log::info("[SMS PRODUCTION (Not Sent)] To: {$receptor} | SalonID: {$salon->id} | Type: {$eventType} | Message: {$message}");
                $status = 'sent_production_simulated'; // تغییر دهید به 'sent' پس از تنظیم پنل
                $externalResponse = 'Production SMS sending is configured but currently simulated.';
            }

            // کسر از موجودی فقط در صورت ارسال موفق (واقعی یا شبیه‌سازی شده)
            if (str_starts_with($status, 'sent')) {
                $smsCount = $this->calculateSmsCount($message);
                $userSmsBalance->decrement('balance', $smsCount);
            }

            $this->logTransaction($salonOwner->id, $receptor, $message, $status, $eventType, $salon->id, $customerId, $appointmentId, $externalResponse);
            return true;

        } catch (\Exception $e) {
            Log::error("SMS ('{$eventType}') sending critical error to {$receptor} for Salon ID {$salon->id}: " . $e->getMessage());
            $this->logTransaction($salonOwner->id, $receptor, $message, 'error', $eventType, $salon->id, $customerId, $appointmentId, $e->getMessage());
            return false;
        }
    }

    /**
     * جایگزینی placeholder ها در متن قالب
     * مثال: {customer_name} یا {{customer_name}}
     */
    private function compileTemplate(?string $template, array $data): string
    {
        if (is_null($template)) return '';
        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
            $template = str_replace("{" . $key . "}", $value, $template);
        }
        return $template;
    }

    /**
     * متون پیش‌فرض برای زمانی که سالن‌دار قالبی تنظیم نکرده است.
     * این متون باید با متون پیش‌فرض در SalonSmsTemplateController هماهنگ باشند.
     */
    private function getDefaultTextForEventType(string $eventType, array $data = []): string
    {
        switch ($eventType) {
            case 'appointment_confirmation':
                return "مشتری گرامی {customer_name}، نوبت شما در سالن {salon_name} برای تاریخ {appointment_date} ساعت {appointment_time} ثبت شد.";
            case 'appointment_reminder':
                return "یادآوری نوبت:\nمشتری گرامی {customer_name}، فردا ({appointment_date}) ساعت {appointment_time} در سالن {salon_name} منتظر شما هستیم.";
            case 'appointment_cancellation':
                return "مشتری گرامی {customer_name}، نوبت شما در سالن {salon_name} برای تاریخ {appointment_date} ساعت {appointment_time} لغو گردید.";
            case 'appointment_modification':
                return "مشتری گرامی {customer_name}، نوبت شما در سالن {salon_name} به تاریخ {appointment_date} ساعت {appointment_time} تغییر یافت.";
            case 'birthday_greeting':
                return "زادروزتان خجسته باد، {customer_name} عزیز! با آرزوی بهترین‌ها. سالن {salon_name}";
            case 'service_specific_notes':
                return "مشتری گرامی {customer_name}، برای نوبت {service_name} شما در {appointment_date} ساعت {appointment_time}:\n{service_specific_notes}\nسالن {salon_name}";
            default:
                return "پیام از طرف سالن {salon_name}.";
        }
    }

    // (متد logTransaction از پاسخ قبلی اینجا قرار می‌گیرد و اصلاح شده)
    protected function logTransaction(
        int $userId,
        string $receptor,
        string $message,
        string $status,
        string $smsType,
        ?int $salonId,
        ?int $customerId,
        ?int $appointmentId,
        ?string $externalResponse = null
    ): void
    {
        SmsTransaction::create([
            'user_id' => $userId,
            'salon_id' => $salonId,
            'customer_id' => $customerId,
            'appointment_id' => $appointmentId,
            'receptor' => $receptor,
            'sms_type' => $smsType,
            'content' => $message,
            'sent_at' => now(),
            'status' => $status,
            'external_response' => $externalResponse
        ]);
    }


    // --- متدهای عمومی برای ارسال انواع پیامک ---

    public function sendOtp(string $mobile, string $otp, User $userToCharge): bool
    {
        // OTP معمولا قالب سراسری دارد و توسط سالن شخصی‌سازی نمی‌شود.
        $message = "کد تایید شما برای BCRM: {$otp}";

        // بررسی موجودی
        $userSmsBalance = UserSmsBalance::firstOrCreate(['user_id' => $userToCharge->id], ['balance' => 0]);
        if ($userSmsBalance->balance <= 0) {
            Log::warning("User ID {$userToCharge->id} has insufficient SMS balance for OTP to {$mobile}.");
            return false;
        }

        try {
            if (env('APP_ENV') !== 'production' || !$this->apiKey || $this->apiKey === 'YOUR_DEFAULT_KEY_IF_NOT_SET') {
                Log::info("[SMS SIMULATION - OTP] To: {$mobile} | Message: {$message}");
                $status = 'sent_simulated_otp';
            } else {
                // کد ارسال واقعی OTP
                // $response = Http::...
                // if ($response->successful() && ...) $status = 'sent_otp'; else ...
                $status = 'sent_production_simulated_otp'; // موقت
            }

            if (str_starts_with($status, 'sent')) {
                $smsCount = $this->calculateSmsCount($message);
                $userSmsBalance->decrement('balance', $smsCount);
            }
            $this->logTransaction($userToCharge->id, $mobile, $message, $status, 'otp_verification', null, null, null, 'OTP Simulated/Sent');
            return true;
        } catch (\Exception $e) {
            Log::error("OTP SMS sending error to {$mobile}: " . $e->getMessage());
            $this->logTransaction($userToCharge->id, $mobile, $message, 'error_otp', 'otp_verification', null, null, null, $e->getMessage());
            return false;
        }
    }

    public function sendAppointmentConfirmation(Customer $customer, Appointment $appointment, Salon $salon): bool
    {
        $dataForTemplate = [
            'customer_name' => $customer->name,
            'salon_name' => $salon->name,
            'appointment_date' => Jalalian::fromCarbon(Carbon::parse($appointment->appointment_date))->format('Y/m/d'),
            'appointment_time' => $appointment->appointment_time,
            'staff_name' => $appointment->staff ? $appointment->staff->full_name : 'پرسنل محترم',
            'services_list' => $appointment->services->pluck('name')->implode('، '),
            'appointment_cost' => number_format($appointment->cost ?: 0) . ' تومان',
        ];
        return $this->sendMessageUsingTemplate(
            $salon,
            'appointment_confirmation',
            $customer->phone_number,
            $salon->user,
            $dataForTemplate,
            $customer->id,
            $appointment->id
        );
    }

    public function sendAppointmentModification(Customer $customer, Appointment $appointment, Salon $salon): bool
    {
        $dataForTemplate = [
            'customer_name' => $customer->name,
            'salon_name' => $salon->name,
            'appointment_date' => Jalalian::fromCarbon(Carbon::parse($appointment->appointment_date))->format('Y/m/d'),
            'appointment_time' => $appointment->appointment_time,
            'staff_name' => $appointment->staff ? $appointment->staff->full_name : 'پرسنل محترم',
            'services_list' => $appointment->services->pluck('name')->implode('، '),
        ];
        return $this->sendMessageUsingTemplate(
            $salon,
            'appointment_modification',
            $customer->phone_number,
            $salon->user,
            $dataForTemplate,
            $customer->id,
            $appointment->id
        );
    }

    public function sendAppointmentCancellation(Customer $customer, Appointment $appointment, Salon $salon): bool
    {
        $dataForTemplate = [
            'customer_name' => $customer->name,
            'salon_name' => $salon->name,
            'appointment_date' => Jalalian::fromCarbon(Carbon::parse($appointment->appointment_date))->format('Y/m/d'),
            'appointment_time' => $appointment->appointment_time,
        ];
        return $this->sendMessageUsingTemplate(
            $salon,
            'appointment_cancellation',
            $customer->phone_number,
            $salon->user,
            $dataForTemplate,
            $customer->id,
            $appointment->id
        );
    }

    public function sendAppointmentReminder(Customer $customer, Appointment $appointment, Salon $salon): bool
    {
        $dataForTemplate = [
            'customer_name' => $customer->name,
            'salon_name' => $salon->name,
            'appointment_date' => Jalalian::fromCarbon(Carbon::parse($appointment->appointment_date))->format('Y/m/d'),
            'appointment_time' => $appointment->appointment_time,
            'staff_name' => $appointment->staff ? $appointment->staff->full_name : 'پرسنل محترم',
        ];
        return $this->sendMessageUsingTemplate(
            $salon,
            'appointment_reminder',
            $customer->phone_number,
            $salon->user,
            $dataForTemplate,
            $customer->id,
            $appointment->id
        );
    }

    public function sendBirthdayGreeting(Customer $customer, Salon $salon): bool
    {
        $dataForTemplate = [
            'customer_name' => $customer->name,
            'salon_name' => $salon->name,
            // 'customer_birth_date' => $customer->jalali_birth_date, // اگر در مدل Customer اکسسور دارید
        ];
        return $this->sendMessageUsingTemplate(
            $salon,
            'birthday_greeting',
            $customer->phone_number,
            $salon->user,
            $dataForTemplate,
            $customer->id
        );
    }

    public function sendServiceSpecificNotes(Customer $customer, Appointment $appointment, Service $service, string $specificNotes, Salon $salon): bool
    {
        $dataForTemplate = [
            'customer_name' => $customer->name,
            'salon_name' => $salon->name,
            'service_name' => $service->name,
            'service_specific_notes' => $specificNotes,
            'appointment_date' => Jalalian::fromCarbon(Carbon::parse($appointment->appointment_date))->format('Y/m/d'),
            'appointment_time' => $appointment->appointment_time,
        ];
        return $this->sendMessageUsingTemplate(
            $salon,
            'service_specific_notes', // event_type جدید
            $customer->phone_number,
            $salon->user,
            $dataForTemplate,
            $customer->id,
            $appointment->id
        );
    }

    public function sendCustomMessage(User $user, string $receptor, string $message): bool
    {
        $userSmsBalance = UserSmsBalance::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        $smsCount = $this->calculateSmsCount($message);

        if ($userSmsBalance->balance < $smsCount) {
            Log::warning("User ID {$user->id} has insufficient SMS balance to send custom message to {$receptor}.");
            return false;
        }

        try {
            if (env('APP_ENV') !== 'production' || !$this->apiKey || $this->apiKey === 'YOUR_DEFAULT_KEY_IF_NOT_SET') {
                Log::info("[SMS SIMULATION - CUSTOM] To: {$receptor} | Message: {$message}");
                $status = 'sent_simulated_custom';
            } else {
                // Real send logic here
                $status = 'sent_production_simulated_custom';
            }

            if (str_starts_with($status, 'sent')) {
                $userSmsBalance->decrement('balance', $smsCount);
            }

            $this->logTransaction($user->id, $receptor, $message, $status, 'custom', null, null, null, 'Custom message simulated/sent.');
            return true;
        } catch (\Exception $e) {
            Log::error("Custom SMS sending error to {$receptor}: " . $e->getMessage());
            $this->logTransaction($user->id, $receptor, $message, 'error_custom', 'custom', null, null, null, $e->getMessage());
            return false;
        }
    }

    public function calculateSmsCount(string $message): int
    {
        $characterCount = mb_strlen($message);
        return (int)ceil($characterCount / $this->smsCharacterLimit);
    }
}
