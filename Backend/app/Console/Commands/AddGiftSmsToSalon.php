<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SalonSmsBalance;
use App\Models\SmsTransaction;
use App\Models\UserPackage;

class AddGiftSmsToSalon extends Command
{
    protected $signature = 'salon:add-gift-sms {salon_id} {--admin_id=}';
    protected $description = 'Add gift SMS to salon based on active package';

    public function handle()
    {
        $salonId = $this->argument('salon_id');
        
        // Find the active package for the salon
        $activePackage = UserPackage::where('salon_id', $salonId)->where('status', 'active')->first();

        if (!$activePackage) {
            $this->error("No active package found for salon {$salonId}");
            return 1;
        }

        $package = $activePackage->package;
        $this->info("Package: {$package->name}, Gift SMS: {$package->gift_sms_count}");

        if ($package->gift_sms_count <= 0) {
            $this->error("Package has no gift SMS");
            return 1;
        }

        // Get current SMS balance
        $salonSmsBalance = SalonSmsBalance::firstOrCreate(['salon_id' => $salonId], ['balance' => 0]);
        $oldBalance = $salonSmsBalance->balance;

        // Add gift SMS
        $salonSmsBalance->increment('balance', $package->gift_sms_count);

        // Create transaction record
        $transactionData = [
            'salon_id' => $salonId,
            'type' => 'gift',
            'amount' => $package->gift_sms_count,
            'description' => 'تصحیح: اضافه کردن پیامک هدیه پکیج ' . $package->name,
            'status' => 'completed',
        ];
        
        // Add admin_id if provided
        if ($this->option('admin_id')) {
            $transactionData['approved_by'] = $this->option('admin_id');
        }
        
        SmsTransaction::create($transactionData);

        $newBalance = $salonSmsBalance->fresh()->balance;
        $this->info("Old Balance: {$oldBalance}, New Balance: {$newBalance}");
        $this->info("Added {$package->gift_sms_count} gift SMS successfully!");

        return 0;
    }
}