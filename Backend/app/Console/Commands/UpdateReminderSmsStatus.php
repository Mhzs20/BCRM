<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class UpdateReminderSmsStatus extends Command
{
    protected $signature = 'sms:update-reminder-status';
    protected $description = 'بررسی و آپدیت وضعیت پیامک‌های یادآوری که هنوز pending هستند.';

    public function handle()
    {
        $pendingAppointments = Appointment::where('reminder_sms_status', 'pending')
            ->whereNotNull('reminder_sms_message_id')
            ->get();

        if ($pendingAppointments->isEmpty()) {
            $this->info('هیچ پیامک یادآوری با وضعیت pending وجود ندارد.');
            return 0;
        }

        $smsService = app(SmsService::class);
        $messageIds = $pendingAppointments->pluck('reminder_sms_message_id')->toArray();
        $statuses = $smsService->checkSmsStatus($messageIds);

        foreach ($pendingAppointments as $appointment) {
            $msgId = $appointment->reminder_sms_message_id;
            if (!isset($statuses[$msgId])) continue;
            $newStatus = $smsService->mapKavenegarStatusToInternal($statuses[$msgId]);
            if ($newStatus !== 'pending') {
                $appointment->reminder_sms_status = $newStatus;
                $appointment->save();
                $this->info("Appointment #{$appointment->id} status updated to {$newStatus}");
            }
        }
        $this->info('بررسی و آپدیت وضعیت پیامک‌های یادآوری انجام شد.');
        return 0;
    }
}
