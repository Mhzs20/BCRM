<?php

namespace App\Jobs;

use App\Models\SatisfactionSurveySetting;
use App\Models\SatisfactionSurveyGroupSetting;
use App\Models\SatisfactionSurveyLog;
use App\Models\Appointment;
use App\Models\SalonSmsTemplate;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessScheduledSatisfactionSurveys implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle()
    {
        Log::info('Starting ProcessScheduledSatisfactionSurveys job');

        try {
            $now = Carbon::now();
            
            // Get all completed appointments from the last 48 hours (to cover all time ranges)
            $startTime = $now->copy()->subHours(48);
            
            $appointments = Appointment::where('status', 'completed')
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', $startTime)
                ->with(['salon', 'customer.customerGroups', 'services'])
                ->get();

            Log::info('Found ' . $appointments->count() . ' completed appointments to check');

            foreach ($appointments as $appointment) {
                $this->processSatisfactionSurvey($appointment, $now);
            }

            Log::info('Completed ProcessScheduledSatisfactionSurveys job');
        } catch (\Exception $e) {
            Log::error('Error in ProcessScheduledSatisfactionSurveys: ' . $e->getMessage());
        }
    }

    private function processSatisfactionSurvey(Appointment $appointment, Carbon $now)
    {
        try {
            $salon = $appointment->salon;
            $customer = $appointment->customer;

            if (!$salon || !$customer || !$customer->phone_number) {
                return;
            }

            // Get satisfaction survey settings for this salon
            $setting = SatisfactionSurveySetting::where('salon_id', $salon->id)
                ->where('is_global_active', true)
                ->first();

            if (!$setting) {
                return;
            }

            // Get customer group settings
            $customerGroups = $customer->customerGroups;
            if ($customerGroups->isEmpty()) {
                return;
            }

            foreach ($customerGroups as $customerGroup) {
                $groupSetting = SatisfactionSurveyGroupSetting::where('satisfaction_survey_setting_id', $setting->id)
                    ->where('customer_group_id', $customerGroup->id)
                    ->where('is_active', true)
                    ->first();

                if ($groupSetting) {
                    $this->checkAndSendSurvey($appointment, $salon, $customer, $setting, $groupSetting, $now);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing satisfaction survey for appointment ' . $appointment->id . ': ' . $e->getMessage());
        }
    }

    private function checkAndSendSurvey($appointment, $salon, $customer, $setting, $groupSetting, $now)
    {
        try {

            // Calculate when to send
            $completedAt = Carbon::parse($appointment->completed_at);
            $sendAt = $completedAt->copy()->addHours($groupSetting->send_hours_after);

            // Check if it's time to send (within 1 hour window)
            $timeDiff = $now->diffInMinutes($sendAt, false);
            
            // Send if we're within the window (between -60 and 60 minutes)
            if ($timeDiff >= -60 && $timeDiff <= 60) {
                // Check if we haven't already sent for this appointment and time slot
                $existingLog = SatisfactionSurveyLog::where('appointment_id', $appointment->id)
                    ->whereBetween('scheduled_at', [
                        $sendAt->copy()->subHour(),
                        $sendAt->copy()->addHour()
                    ])
                    ->whereIn('status', ['sent', 'pending'])
                    ->first();

                if (!$existingLog) {
                    $this->sendSurveyMessage($appointment, $salon, $customer, $setting, $sendAt);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing satisfaction survey for appointment ' . $appointment->id . ': ' . $e->getMessage());
        }
    }

    private function sendSurveyMessage(Appointment $appointment, $salon, $customer, SatisfactionSurveySetting $setting, Carbon $sendAt)
    {
        try {
            // Get template
            $template = SalonSmsTemplate::find($setting->template_id);
            if (!$template) {
                Log::warning('Template not found for satisfaction survey: ' . $setting->template_id);
                return;
            }

            // Replace placeholders
            $message = $this->replaceTemplatePlaceholders($template->template, $customer, $salon, $appointment);

            // Send SMS (pass null as sender to use default from settings)
            $smsService = app(SmsService::class);
            $result = $smsService->sendSms($customer->phone_number, $message, null);

            // Determine status based on API response
            $status = 'failed';
            $messageId = null;
            if ($result && is_array($result) && count($result) > 0) {
                $entry = $result[0];
                $kavenegarStatus = $entry['status'] ?? null;
                $messageId = $entry['messageid'] ?? null;
                $status = $kavenegarStatus !== null 
                    ? $smsService->mapKavenegarStatusToInternal($kavenegarStatus) 
                    : 'pending';
            }

            // Mark the appointment as survey sent
            if ($status !== 'failed') {
                $appointment->survey_sms_sent_at = Carbon::now();
                $appointment->satisfaction_sms_status = $status;
                if ($messageId) {
                    $appointment->satisfaction_sms_message_id = $messageId;
                }
                $appointment->save();
            }

            // Log the send
            SatisfactionSurveyLog::create([
                'appointment_id' => $appointment->id,
                'customer_id' => $customer->id,
                'salon_id' => $salon->id,
                'scheduled_at' => $sendAt,
                'sent_at' => $status !== 'failed' ? Carbon::now() : null,
                'status' => $status,
                'message_id' => $messageId,
                'error_message' => $status === 'failed' ? 'SMS send failed' : null,
            ]);

            if ($result) {
                Log::info('Satisfaction survey SMS sent successfully for appointment: ' . $appointment->id);
            } else {
                Log::warning('Failed to send satisfaction survey SMS for appointment: ' . $appointment->id);
            }
        } catch (\Exception $e) {
            Log::error('Error sending satisfaction survey message: ' . $e->getMessage());
            
            // Log the failure
            SatisfactionSurveyLog::create([
                'appointment_id' => $appointment->id,
                'customer_id' => $customer->id,
                'salon_id' => $salon->id,
                'scheduled_at' => $sendAt,
                'sent_at' => null,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    private function replaceTemplatePlaceholders($content, $customer, $salon, $appointment)
    {
        $serviceName = '';
        if ($appointment->services && $appointment->services->isNotEmpty()) {
            $serviceName = $appointment->services->pluck('name')->implode('، ');
        }
        
        $appointmentDate = '';
        if ($appointment->appointment_date) {
            $appointmentDate = \Morilog\Jalali\Jalalian::fromCarbon(Carbon::parse($appointment->appointment_date))->format('Y/m/d');
        }
        
        // Generate unique survey link for this appointment
        $surveyLink = $this->generateSurveyLink($appointment, $salon);
        
        $replacements = [
            '{نام_مشتری}' => $customer->name ?? '',
            '{نام_سالن}' => $salon->name ?? '',
            '{نام_خدمت}' => $serviceName,
            '{تاریخ_نوبت}' => $appointmentDate,
            '{لینک_رضایت_سنجی}' => $surveyLink,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    private function generateSurveyLink($appointment, $salon)
    {
        // Use appointment's existing hash for the satisfaction survey page
        $baseUrl = config('app.url');
        
        return "{$baseUrl}/s/{$appointment->hash}";
    }
}
