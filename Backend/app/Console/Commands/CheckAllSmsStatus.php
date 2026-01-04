<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CheckSmsStatus;
use Illuminate\Support\Facades\Log;

class CheckAllSmsStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:check-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'بررسی و به‌روزرسانی وضعیت تمام پیامک‌های pending (Appointments + Manual SMS Transactions)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('شروع بررسی وضعیت پیامک‌ها...');
        
        try {
            CheckSmsStatus::dispatchSync();
            $this->info('✓ بررسی وضعیت پیامک‌ها با موفقیت انجام شد.');
            Log::info('CheckAllSmsStatus command executed successfully.');
            return 0;
        } catch (\Exception $e) {
            $this->error('خطا در بررسی وضعیت پیامک‌ها: ' . $e->getMessage());
            Log::error('CheckAllSmsStatus command failed: ' . $e->getMessage());
            return 1;
        }
    }
}
