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
                if (!$force && $pivot->send_time !== $currentTime) {
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
