<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAutomaticCustomerFollowupJob;
use App\Models\CustomerFollowUpSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessCustomerFollowups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'followup:process-customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'پردازش و ارسال خودکار پیامک‌های پیگیری مشتریان برای تمام سالن‌های فعال';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('شروع پردازش پیگیری خودکار مشتریان...');
        
        try {
            // پیدا کردن تمام سالن‌هایی که تنظیمات پیگیری فعال دارند
            $activeFollowupSettings = CustomerFollowUpSetting::where('is_global_active', true)
                ->with('groupSettings')
                ->get();

            if ($activeFollowupSettings->isEmpty()) {
                $this->info('هیچ سالنی با تنظیمات پیگیری فعال یافت نشد.');
                Log::info('No active customer followup settings found.');
                return 0;
            }

            $dispatchedCount = 0;

            foreach ($activeFollowupSettings as $setting) {
                // بررسی که حداقل یک گروه فعال دارد
                $hasActiveGroups = $setting->groupSettings()->where('is_active', true)->exists();
                
                if ($hasActiveGroups) {
                    ProcessAutomaticCustomerFollowupJob::dispatch($setting->salon_id);
                    $dispatchedCount++;
                    $this->info("Job dispatched برای سالن ID: {$setting->salon_id}");
                }
            }

            $this->info("✓ تعداد {$dispatchedCount} Job پیگیری مشتری با موفقیت dispatch شد.");
            Log::info("ProcessCustomerFollowups command completed. Dispatched {$dispatchedCount} jobs.");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('خطا در پردازش پیگیری مشتریان: ' . $e->getMessage());
            Log::error('ProcessCustomerFollowups command failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return 1;
        }
    }
}
