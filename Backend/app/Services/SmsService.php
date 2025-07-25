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
    protected $senderNumber; // This will be fetched from salon settings or Kavenegar default
    protected $baseUri;
    protected $smsCharacterLimit;

    public function __construct()
    {
        $this->apiKey = config('kavenegar.api_key');
        $this->baseUri = config('kavenegar.base_uri');
        
        $smsCharacterLimitSetting = Setting::where('key', 'sms_character_limit')->first();
        $this->smsCharacterLimit = $smsCharacterLimitSetting ? (int)$smsCharacterLimitSetting->value : 70;

        if (env('APP_ENV') !== 'testing' && (!$this->apiKey || $this->apiKey === 'YOUR_DEFAULT_KEY_IF_NOT_SET')) {
            Log::warning('Kavenegar API Key is not configured properly in .env file. SMS sending will be simulated.');
        }
    }

    /**
     * Sends an SMS message via Kavenegar API.
     *
     * @param string $receptor The recipient's phone number(s), comma-separated.
     * @param string $message The SMS text.
     * @param string|null $sender The sender number. If null, Kavenegar's default is used.
     * @param int|null $localId A local ID for preventing duplicate sends.
     * @return array|null Returns Kavenegar's 'entries' array on success, null on failure.
     */
    public function sendSms(string $receptor, string $message, ?string $sender = null, ?int $localId = null): ?array
    {
        if (!$this->apiKey || $this->apiKey === 'YOUR_DEFAULT_KEY_IF_NOT_SET') {
            Log::warning("Kavenegar API Key is not configured. Simulating SMS send.");
            return null;
        }

        // The user has specified that the server has issues with modern TLS handshakes.
        // We will use a simplified cURL request as requested.
        $url = "https://api.kavenegar.com/v1/{$this->apiKey}/sms/send.json";
        
        $data = [
            'receptor' => $receptor,
            'sender'   => $sender ?: '9982001323', // Use provided sender or default
            'message'  => $message,
        ];

        if ($localId) {
            $data['localid'] = $localId;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Log::error("Kavenegar cURL Error for {$receptor}: " . $curlError);
            return null;
        }

        $res = json_decode($response, true);

        if (isset($res['return']['status']) && $res['return']['status'] === 200) {
            Log::info("Kavenegar SMS sent successfully via cURL to {$receptor}. Response: " . $response);
            return $res['entries'] ?? null;
        } else {
            Log::error("Kavenegar SMS sending failed for {$receptor}. Full API Response: " . $response);
            return null;
        }
    }

    /**
     * Checks the status of SMS messages via Kavenegar API.
     *
     * @param array $messageIds Array of Kavenegar message IDs.
     * @return array An associative array where keys are message IDs and values are their statuses (e.g., ['123' => 10]).
     */
    public function checkSmsStatus(array $messageIds): array
    {
        if (empty($messageIds)) {
            return [];
        }

        if (!$this->apiKey) {
            Log::info("[KAVENEGAR STATUS SIMULATION] Checking IDs: " . implode(',', $messageIds));
            $simulatedStatuses = [];
            foreach ($messageIds as $id) {
                $simulatedStatuses[$id] = rand(0, 1) ? 10 : 11; // Simulate delivered or undelivered
            }
            return $simulatedStatuses;
        }

        try {
            $url = $this->baseUri . $this->apiKey . '/sms/status.json';
            $params = [
                'messageid' => implode(',', $messageIds),
            ];

            $response = Http::get($url, $params);

            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['return']['status']) && $responseData['return']['status'] === 200) {
                    $statuses = [];
                    foreach ($responseData['entries'] as $entry) {
                        $statuses[$entry['messageid']] = $entry['status'];
                    }
                    return $statuses;
                } else {
                    Log::error("Kavenegar SMS status check failed. API Error: " . json_encode($responseData));
                    return [];
                }
            } else {
                Log::error("Kavenegar HTTP request failed for status check. Status: {$response->status()} Body: " . $response->body());
                return [];
            }
        } catch (\Exception $e) {
            Log::error("Kavenegar SMS status check exception: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Maps Kavenegar status codes to internal application statuses.
     *
     * @param int $kavenegarStatus The status code from Kavenegar.
     * @return string Our internal status: 'not_sent', 'pending', 'sent', 'failed'.
     */
    public function mapKavenegarStatusToInternal(int $kavenegarStatus): string
    {
        switch ($kavenegarStatus) {
            case 1: // در صف ارسال
            case 2: // زمان بندی شده
            case 4: // ارسال شده به مخابرات
            case 5: // ارسال شده به مخابرات
                return 'pending';
            case 10: // رسیده به گیرنده
                return 'sent';
            case 6:  // خطا در ارسال پیام
            case 11: // نرسیده به گیرنده
            case 13: // لغو شده
            case 14: // بلاک شده
                return 'failed'; // Using 'failed' for all non-delivered statuses
            case 100: // شناسه پیامک نامعتبر است
            default:
                return 'not_sent'; // Default or unknown status
        }
    }
    /**
     * Sends a free OTP message that does not use user's credit.
     *
     * @param string $receptor The recipient's phone number.
     * @param string $otpCode The One-Time Password.
     * @return bool True if the sending was initiated, false otherwise.
     */
    public function sendOtp(string $receptor, string $otpCode): bool
    {
        // As per the request for Android automatic OTP verification.
        $appName = 'بیوتی سی آر ام';
        $appSignature = '0iHp5lSm3eN'; // This should be stored securely, e.g., in config/app.php

        $message = "<#>\n" .
                   "کد ورود شما\n" .
                   "{$otpCode}\n" .
                   $appName . "\n" .
                   $appSignature;

        Log::info("Sending Android-formatted OTP to {$receptor}. This is a free transaction and will not be deducted from any user balance.");

        $sender = '9982001323';
        $smsEntries = $this->sendSms($receptor, $message, $sender);

        return !is_null($smsEntries);
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
    ): array {
        $smsTemplate = $salon->getSmsTemplate($eventType);

        $templateText = null;
        if ($smsTemplate && $smsTemplate->is_active) {
            $templateText = $smsTemplate->template;
        }

        // If no active custom template, use the system default
        if (empty($templateText)) {
            $templateText = $this->getDefaultTextForEventType($eventType, $dataForTemplate);
        }
        
        $message = $this->compileTemplate($templateText, $dataForTemplate);

        if (empty(trim($message))) {
            Log::warning("Compiled message for event '{$eventType}' for Salon ID {$salon->id} is empty. SMS to {$receptor} not sent.");
            return ['status' => 'success', 'message' => 'پیام خالی است و ارسال نشد.'];
        }

        // Re-introducing the balance check as per user request.
        $smsCount = $this->calculateSmsCount($message);
        if ($salon->sms_balance < $smsCount) {
            Log::warning("User ID {$salonOwner->id} (Salon: {$salon->id}) has insufficient SMS balance to send '{$eventType}' to {$receptor}. Balance: {$salon->sms_balance}, Required: {$smsCount}");
            return ['status' => 'error', 'message' => 'اعتبار پیامک کافی نیست.'];
        }

        try {
            // Pass null as sender to use the default from sendSms method
            $smsEntries = $this->sendSms($receptor, $message, null, $appointmentId);

            if ($smsEntries && !empty($smsEntries)) {
                // Deduct balance only if SMS was actually sent
                $salon->decrement('sms_balance', $smsCount);
                $firstEntry = $smsEntries[0]; // Kavenegar returns an array of entries
                $messageId = $firstEntry['messageid'] ?? null;
                $kavenegarStatus = $firstEntry['status'] ?? null;
                $internalStatus = $this->mapKavenegarStatusToInternal($kavenegarStatus);

                // Update appointment status if applicable
                if ($appointmentId) {
                    $appointment = Appointment::find($appointmentId);
                    if ($appointment) {
                        // Determine which status field to update based on eventType
                        if ($eventType === 'appointment_reminder') {
                            $appointment->reminder_sms_status = $internalStatus;
                            $appointment->reminder_sms_message_id = $messageId; // Assuming we add this column later
                        } elseif ($eventType === 'satisfaction_survey') { // Need to define this eventType
                            $appointment->satisfaction_sms_status = $internalStatus;
                            $appointment->satisfaction_sms_message_id = $messageId; // Assuming we add this column later
                        }
                        $appointment->save();
                    }
                }

                $this->logTransaction($salonOwner->id, $receptor, $message, $internalStatus, $eventType, $salon->id, $customerId, $appointmentId, json_encode($smsEntries));
                return ['status' => 'success', 'message' => 'پیامک با موفقیت ارسال شد.'];
            } else {
                Log::error("Kavenegar sendSms returned no entries or failed for '{$eventType}' to {$receptor}.");
                $this->logTransaction($salonOwner->id, $receptor, $message, 'failed', $eventType, $salon->id, $customerId, $appointmentId, 'Kavenegar API call failed or returned empty.');
                return ['status' => 'error', 'message' => 'خطا در ارسال پیامک.'];
            }

        } catch (\Exception $e) {
            Log::error("SMS ('{$eventType}') sending critical exception to {$receptor} for Salon ID {$salon->id}: " . $e->getMessage());
            $this->logTransaction($salonOwner->id, $receptor, $message, 'error', $eventType, $salon->id, $customerId, $appointmentId, $e->getMessage());
            return ['status' => 'error', 'message' => 'خطای سیستمی در ارسال پیامک.'];
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
                return "مشتری گرامی {customer_name}، نوبت شما در سالن {salon_name} برای تاریخ {appointment_date} ساعت {appointment_time} ثبت شد. برای مشاهده جزئیات نوبت خود، روی لینک زیر کلیک کنید:\n{details_url}";
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
            case 'satisfaction_survey': // New default text for satisfaction survey
                return "مشتری گرامی {customer_name}، از حضور شما در سالن {salon_name} سپاسگزاریم. لطفا با تکمیل نظرسنجی ما را در بهبود خدمات یاری کنید: [لینک نظرسنجی]";
            default:
                return "پیام از طرف سالن {salon_name}.";
        }
    }

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

    // Removed sendOtp and sendCustomMessage as per plan
    // public function sendOtp(...) { ... }
    // public function sendCustomMessage(...) { ... }

    public function sendAppointmentConfirmation(Customer $customer, Appointment $appointment, Salon $salon, ?string $detailsUrl = null): array
    {
        $dataForTemplate = [
            'customer_name' => $customer->name,
            'salon_name' => $salon->name,
            'appointment_date' => Jalalian::fromCarbon(Carbon::parse($appointment->appointment_date))->format('Y/m/d'),
            'appointment_time' => Carbon::parse($appointment->start_time)->format('H:i'),
            'staff_name' => $appointment->staff ? $appointment->staff->full_name : 'پرسنل محترم',
            'services_list' => $appointment->services->pluck('name')->implode('، '),
            'appointment_cost' => number_format($appointment->total_price ?: 0) . ' تومان',
            'details_url' => $detailsUrl, // Add the URL to the template data
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

    public function sendAppointmentModification(Customer $customer, Appointment $appointment, Salon $salon): array
    {
        $dataForTemplate = [
            'customer_name' => $customer->name,
            'salon_name' => $salon->name,
            'appointment_date' => Jalalian::fromCarbon(Carbon::parse($appointment->appointment_date))->format('Y/m/d'),
            'appointment_time' => Carbon::parse($appointment->start_time)->format('H:i'), // Correctly format the time
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

    public function sendAppointmentCancellation(Customer $customer, Appointment $appointment, Salon $salon): array
    {
        $dataForTemplate = [
            'customer_name' => $customer->name,
            'salon_name' => $salon->name,
            'appointment_date' => Jalalian::fromCarbon(Carbon::parse($appointment->appointment_date))->format('Y/m/d'),
            'appointment_time' => Carbon::parse($appointment->start_time)->format('H:i'),
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

    public function sendAppointmentReminder(Customer $customer, Appointment $appointment, Salon $salon): array
    {
        $dataForTemplate = [
            'customer_name' => $customer->name,
            'salon_name' => $salon->name,
            'appointment_date' => Jalalian::fromCarbon(Carbon::parse($appointment->appointment_date))->format('Y/m/d'),
            'appointment_time' => Carbon::parse($appointment->start_time)->format('H:i'),
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

    public function sendBirthdayGreeting(Customer $customer, Salon $salon): array
    {
        $dataForTemplate = [
            'customer_name' => $customer->name,
            'salon_name' => $salon->name,
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

    public function sendSatisfactionSurvey(Customer $customer, Appointment $appointment, Salon $salon): array
    {
        $dataForTemplate = [
            'customer_name' => $customer->name,
            'salon_name' => $salon->name,
            'appointment_date' => Jalalian::fromCarbon(Carbon::parse($appointment->appointment_date))->format('Y/m/d'),
            'appointment_time' => Carbon::parse($appointment->start_time)->format('H:i'),
            // Add survey link here if available
        ];
        return $this->sendMessageUsingTemplate(
            $salon,
            'satisfaction_survey',
            $customer->phone_number,
            $salon->user,
            $dataForTemplate,
            $customer->id,
            $appointment->id
        );
    }

    public function calculateSmsCount(string $message): int
    {
        $characterCount = mb_strlen($message);
        return (int)ceil($characterCount / $this->smsCharacterLimit);
    }
}
