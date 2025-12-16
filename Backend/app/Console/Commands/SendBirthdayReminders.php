<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BirthdayReminder;
use App\Models\Customer;
use App\Models\Salon;
use Carbon\Carbon;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class SendBirthdayReminders extends Command
{
    protected $signature = 'reminders:send-birthday {--force : Force send regardless of time}';
    protected $description = 'Send birthday SMS reminders to customer groups based on settings';

    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        parent::__construct();
        $this->smsService = $smsService;
    }

    public function handle()
    {
        $now = Carbon::now();
        $currentTime = $now->format('H:i');
        $force = $this->option('force');

        $this->info("Running birthday reminders check at $currentTime");

        $reminders = BirthdayReminder::where('is_global_active', true)->get();
        
        foreach ($reminders as $reminder) {
            $salon = Salon::find($reminder->salon_id);
            if (!$salon) continue;

            $groups = $reminder->customerGroups()->wherePivot('is_active', true)->get();
            
            foreach ($groups as $group) {
                $pivot = $group->pivot;
                
                // Check time unless forced
                // Changed: Instead of exact match, check if current time >= send_time
                // This ensures SMS is sent even if the exact minute was missed
                $sendTime = Carbon::createFromFormat('H:i', substr($pivot->send_time, 0, 5));
                $currentTimeCarbon = Carbon::createFromFormat('H:i', $currentTime);
                
                if (!$force && $currentTimeCarbon->lt($sendTime)) {
                    continue;
                }
                
                $customers = $group->customers;
                
                foreach ($customers as $customer) {
                    if (!$customer->birth_date) continue;
                    
                    $birthday = Carbon::parse($customer->birth_date);
                    
                    // Calculate the date we should be sending the reminder
                    // If send_days_before is 0, we send on birthday.
                    // If send_days_before is 1, we send 1 day before birthday.
                    
                    // So if today + send_days_before matches birthday (month/day)
                    $targetDate = $now->copy()->addDays($pivot->send_days_before);
                    
                    if ($birthday->month === $targetDate->month && $birthday->day === $targetDate->day) {
                        // Check if birthday SMS already sent today
                        $alreadySent = \App\Models\SmsTransaction::where('customer_id', $customer->id)
                            ->where('salon_id', $salon->id)
                            ->where('sms_type', 'birthday_greeting')
                            ->whereDate('created_at', now()->toDateString())
                            ->exists();
                        
                        if ($alreadySent) {
                            $this->warn("Birthday SMS already sent today to {$customer->name}. Skipping.");
                            continue;
                        }
                        
                        $this->info("Sending birthday SMS to {$customer->phone_number} (Customer: {$customer->name}) for group {$group->name}");
                        
                        try {
                            $this->smsService->sendBirthdayGreeting($customer, $salon);
                            $this->info("Sent successfully.");
                        } catch (\Exception $e) {
                            $this->error("Failed to send: " . $e->getMessage());
                            Log::error("Birthday SMS failed: " . $e->getMessage());
                        }
                    }
                }
            }
        }
    }
}
