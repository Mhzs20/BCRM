<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BirthdayReminder;
use App\Models\BirthdayReminderCustomerGroup;
use App\Models\CustomerGroup;
use App\Models\Customer;
use Carbon\Carbon;

class SendBirthdayReminders extends Command
{
    protected $signature = 'reminders:send-birthday';
    protected $description = 'Send birthday SMS reminders to customer groups based on settings';

    public function handle()
    {
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i');

        $reminders = BirthdayReminder::where('is_global_active', true)->get();
        foreach ($reminders as $reminder) {
            $templateId = $reminder->template_id;
            $salonId = $reminder->salon_id;
            $groups = $reminder->customerGroups()->wherePivot('is_active', true)->get();
            foreach ($groups as $group) {
                $pivot = $group->pivot;
                if ($pivot->send_time !== $currentTime) continue;
                $customers = Customer::where('customer_group_id', $group->id)->get();
                foreach ($customers as $customer) {
                    if (!$customer->birthday) continue;
                    $birthday = Carbon::parse($customer->birthday);
                    $reminderDate = $birthday->copy()->subDays($pivot->send_days_before);
                    if ($reminderDate->isSameDay($now)) {
                        // ارسال پیامک با قالب تولد
                        // sendSms($customer->mobile, $templateId, ...)
                        $this->info("Birthday SMS sent to {$customer->mobile} for group {$group->name}");
                    }
                }
            }
        }
    }
}
